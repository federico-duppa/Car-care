<?php

namespace App\Services;

use App\Models\Gasto;
use App\Models\Mantenimiento;
use App\Models\Recordatorio;
use App\Models\Vehiculo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The reminders engine. Computes, at render time, when each reminder is due and
 * whether it is overdue / due soon / ok — from the vehicle's current km and
 * today's date. No background job: status is always derived, never stored.
 *
 * The "anchor" (last time something was done) is resolved per class:
 *  - mantenimiento: latest matching Mantenimiento (so logging one auto-advances
 *    the reminder), falling back to the reminder's baseline.
 *  - documento: the explicit expiry date (base_fecha).
 *  - gasto: the linked Gasto's date plus the period.
 */
class RecordatorioService
{
    /**
     * @return array{proximoKm: int|null, proximaFecha: Carbon|null, restanteKm: int|null, restanteDias: int|null, estado: string}
     */
    public function estado(Recordatorio $r, ?int $kmActual = null, ?Carbon $hoy = null): array
    {
        $hoy = ($hoy ? $hoy->copy() : Carbon::today())->startOfDay();

        [$proximoKm, $proximaFecha] = $this->proximo($r);

        $restanteKm = ($proximoKm !== null && $kmActual !== null) ? $proximoKm - $kmActual : null;
        $restanteDias = $proximaFecha !== null ? (int) $hoy->diffInDays($proximaFecha, false) : null;

        return [
            'proximoKm' => $proximoKm,
            'proximaFecha' => $proximaFecha,
            'restanteKm' => $restanteKm,
            'restanteDias' => $restanteDias,
            'estado' => $this->clasificar($restanteKm, $restanteDias),
        ];
    }

    /** Active reminders for a vehicle, each decorated with its computed `aviso`. */
    public function avisos(Vehiculo $vehiculo, bool $soloAlertas = false): Collection
    {
        $km = $vehiculo->km_actual !== null ? (int) $vehiculo->km_actual : null;
        $hoy = Carbon::today();

        return $vehiculo->recordatorios()
            ->where('activo', true)
            ->with('gasto')
            ->get()
            ->each(fn (Recordatorio $r) => $r->setAttribute('aviso', $this->estado($r, $km, $hoy)))
            ->when($soloAlertas, fn (Collection $c) => $c->filter(fn (Recordatorio $r) => $r->aviso['estado'] !== 'ok'))
            ->sortBy([
                fn (Recordatorio $r) => $this->urgencia($r->aviso['estado']),
                fn (Recordatorio $r) => $r->aviso['restanteDias'] ?? PHP_INT_MAX,
                fn (Recordatorio $r) => $r->aviso['restanteKm'] ?? PHP_INT_MAX,
            ])
            ->values();
    }

    /** How many reminders are overdue or due soon (for the nav badge). */
    public function contar(Vehiculo $vehiculo): int
    {
        return $this->avisos($vehiculo, true)->count();
    }

    /**
     * Keep a clase=gasto reminder in sync with a recurring expense. Creates or
     * updates the linked reminder when the expense is recurrent with a period,
     * and removes it otherwise.
     */
    public function syncDesdeGasto(Gasto $gasto): void
    {
        $existente = Recordatorio::where('gasto_id', $gasto->id)->first();

        if (! $gasto->recurrente || ! $gasto->periodicidad_meses) {
            $existente?->delete();

            return;
        }

        $attrs = [
            'user_id' => $gasto->user_id,
            'vehiculo_id' => $gasto->vehiculo_id,
            'clase' => 'gasto',
            'titulo' => $this->tituloGasto($gasto),
            'tipo' => $gasto->categoria,
            'intervalo_meses' => $gasto->periodicidad_meses,
            'base_fecha' => $gasto->fecha,
            'gasto_id' => $gasto->id,
            'activo' => true,
        ];

        $existente ? $existente->update($attrs) : Recordatorio::create($attrs);
    }

    // ---- Internals ----------------------------------------------------------

    /** @return array{0: int|null, 1: Carbon|null} [proximoKm, proximaFecha] */
    private function proximo(Recordatorio $r): array
    {
        return match ($r->clase) {
            'mantenimiento' => $this->proximoMantenimiento($r),
            'gasto' => [null, $this->proximaFechaGasto($r)],
            default => [null, $r->base_fecha?->copy()], // documento
        };
    }

    private function proximoMantenimiento(Recordatorio $r): array
    {
        $ultimo = Mantenimiento::where('vehiculo_id', $r->vehiculo_id)
            ->when($r->tipo, fn ($q) => $q->where('tipo', $r->tipo))
            ->orderByDesc('fecha')->orderByDesc('odometro')
            ->first();

        $anclaKm = $ultimo?->odometro ?? $r->base_odometro;
        $anclaFecha = $ultimo?->fecha ?? $r->base_fecha;

        $proximoKm = ($r->intervalo_km && $anclaKm !== null)
            ? (int) $anclaKm + (int) $r->intervalo_km
            : null;
        $proximaFecha = ($r->intervalo_meses && $anclaFecha)
            ? $anclaFecha->copy()->addMonths((int) $r->intervalo_meses)
            : null;

        return [$proximoKm, $proximaFecha];
    }

    private function proximaFechaGasto(Recordatorio $r): ?Carbon
    {
        $base = $r->gasto?->fecha ?? $r->base_fecha;

        if (! $base) {
            return null;
        }

        return $r->intervalo_meses
            ? $base->copy()->addMonths((int) $r->intervalo_meses)
            : $base->copy();
    }

    private function clasificar(?int $restanteKm, ?int $restanteDias): string
    {
        $avisoKm = (int) config('carcare.recordatorios.aviso_km', 500);
        $avisoDias = (int) config('carcare.recordatorios.aviso_dias', 14);

        $estados = [];
        if ($restanteKm !== null) {
            $estados[] = $restanteKm <= 0 ? 'vencido' : ($restanteKm <= $avisoKm ? 'proximo' : 'ok');
        }
        if ($restanteDias !== null) {
            $estados[] = $restanteDias < 0 ? 'vencido' : ($restanteDias <= $avisoDias ? 'proximo' : 'ok');
        }

        if (in_array('vencido', $estados, true)) {
            return 'vencido';
        }
        if (in_array('proximo', $estados, true)) {
            return 'proximo';
        }

        return 'ok';
    }

    private function urgencia(string $estado): int
    {
        return ['vencido' => 0, 'proximo' => 1, 'ok' => 2][$estado] ?? 3;
    }

    private function tituloGasto(Gasto $gasto): string
    {
        $cat = Gasto::CATEGORIAS[$gasto->categoria] ?? ucfirst($gasto->categoria);

        return trim($cat.($gasto->descripcion ? ' · '.$gasto->descripcion : ''));
    }
}
