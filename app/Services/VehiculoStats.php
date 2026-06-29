<?php

namespace App\Services;

use App\Models\Vehiculo;
use Illuminate\Support\Carbon;

/**
 * Computes derived statistics for a vehicle: fuel consumption (full-to-full
 * method), cost per km, and spending totals. Kept deliberately simple and
 * dependency-free so it's easy to follow.
 */
class VehiculoStats
{
    public function __construct(private Vehiculo $vehiculo) {}

    public static function for(Vehiculo $vehiculo): self
    {
        return new self($vehiculo);
    }

    /**
     * Per-interval consumption using the full-tank-to-full-tank method.
     * Returns a list of intervals (oldest first), each with distance, litres
     * burned, L/100km and km/L. Partial fills between two full tanks are
     * accumulated into the next full-tank interval.
     *
     * @return array<int, array{carga: \App\Models\CargaCombustible, distancia: int, litros: float, l_100km: float, km_l: float}>
     */
    public function intervalosConsumo(): array
    {
        $cargas = $this->vehiculo->cargas()
            ->orderBy('odometro')
            ->orderBy('fecha')
            ->get();

        $intervalos = [];
        $odoUltimoLleno = null;
        $litrosAcum = 0.0;

        foreach ($cargas as $carga) {
            if ($odoUltimoLleno !== null) {
                $litrosAcum += (float) $carga->litros;
            }

            if ($carga->tanque_lleno) {
                if ($odoUltimoLleno !== null) {
                    $distancia = $carga->odometro - $odoUltimoLleno;
                    if ($distancia > 0 && $litrosAcum > 0) {
                        $intervalos[] = [
                            'carga' => $carga,
                            'distancia' => $distancia,
                            'litros' => round($litrosAcum, 2),
                            'l_100km' => round($litrosAcum / $distancia * 100, 2),
                            'km_l' => round($distancia / $litrosAcum, 2),
                        ];
                    }
                }
                $odoUltimoLleno = $carga->odometro;
                $litrosAcum = 0.0;
            }
        }

        return $intervalos;
    }

    /** Average consumption across all intervals (L/100km), or null if N/A. */
    public function consumoPromedioL100(): ?float
    {
        $intervalos = $this->intervalosConsumo();
        if (empty($intervalos)) {
            return null;
        }

        $dist = array_sum(array_column($intervalos, 'distancia'));
        $litros = array_sum(array_column($intervalos, 'litros'));

        return $dist > 0 ? round($litros / $dist * 100, 2) : null;
    }

    /** Most recent interval consumption (L/100km), or null. */
    public function consumoUltimoL100(): ?float
    {
        $intervalos = $this->intervalosConsumo();
        return empty($intervalos) ? null : end($intervalos)['l_100km'];
    }

    /** Distance covered according to fuel odometer readings. */
    public function distanciaRecorrida(): int
    {
        $min = $this->vehiculo->cargas()->min('odometro');
        $max = $this->vehiculo->cargas()->max('odometro');

        return ($min !== null && $max !== null) ? (int) ($max - $min) : 0;
    }

    public function totalCombustible(): float
    {
        return (float) $this->vehiculo->cargas()->sum('costo_total');
    }

    public function totalMantenimiento(): float
    {
        return (float) $this->vehiculo->mantenimientos()->sum('costo');
    }

    public function totalGastos(): float
    {
        return (float) $this->vehiculo->gastos()->sum('monto');
    }

    public function totalGeneral(): float
    {
        return $this->totalCombustible() + $this->totalMantenimiento() + $this->totalGastos();
    }

    /** Total cost per km (all spending / distance driven), or null. */
    public function costoPorKm(): ?float
    {
        $dist = $this->distanciaRecorrida();
        return $dist > 0 ? round($this->totalGeneral() / $dist, 2) : null;
    }

    /** Average price per litre across all fills, or null. */
    public function precioLitroPromedio(): ?float
    {
        $litros = (float) $this->vehiculo->cargas()->sum('litros');
        $costo = $this->totalCombustible();

        return $litros > 0 ? round($costo / $litros, 2) : null;
    }

    /** Spending grouped by expense category (descending). */
    public function gastosPorCategoria(): array
    {
        return $this->vehiculo->gastos()
            ->selectRaw('categoria, SUM(monto) as total')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->pluck('total', 'categoria')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** Combined monthly spend for the last N months (oldest first). */
    public function gastoMensual(int $meses = 6): array
    {
        $desde = Carbon::now()->startOfMonth()->subMonths($meses - 1);

        $series = [];
        for ($i = 0; $i < $meses; $i++) {
            $m = (clone $desde)->addMonths($i);
            $series[$m->format('Y-m')] = 0.0;
        }

        $fuentes = [
            [$this->vehiculo->cargas(), 'costo_total'],
            [$this->vehiculo->mantenimientos(), 'costo'],
            [$this->vehiculo->gastos(), 'monto'],
        ];

        foreach ($fuentes as [$query, $col]) {
            $rows = $query->where('fecha', '>=', $desde->toDateString())
                ->get(['fecha', $col]);
            foreach ($rows as $row) {
                $key = Carbon::parse($row->fecha)->format('Y-m');
                if (isset($series[$key])) {
                    $series[$key] += (float) $row->{$col};
                }
            }
        }

        return $series;
    }
}
