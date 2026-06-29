<?php

namespace App\Services;

use App\Models\Vehiculo;
use Illuminate\Support\Carbon;

/**
 * Derived statistics for a vehicle: fuel consumption (full-to-full), cost per
 * km and spending totals. Amounts are returned in the requested currency:
 * 'ARS' uses raw pesos; 'USD' converts each record by its own snapshotted
 * historical rate (usd_rate), falling back to a supplied current rate for
 * records without a snapshot.
 */
class VehiculoStats
{
    public function __construct(
        private Vehiculo $vehiculo,
        private string $moneda = 'ARS',
        private ?float $fallbackRate = null,
    ) {}

    public static function for(Vehiculo $vehiculo, string $moneda = 'ARS', ?float $fallbackRate = null): self
    {
        return new self($vehiculo, $moneda, $fallbackRate);
    }

    // ---- Consumption (currency-independent) --------------------------------

    /**
     * @return array<int, array{carga: \App\Models\CargaCombustible, distancia: int, litros: float, l_100km: float, km_l: float}>
     */
    public function intervalosConsumo(): array
    {
        $cargas = $this->vehiculo->cargas()
            ->orderBy('odometro')->orderBy('fecha')->get();

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

    public function consumoUltimoL100(): ?float
    {
        $intervalos = $this->intervalosConsumo();
        return empty($intervalos) ? null : end($intervalos)['l_100km'];
    }

    public function distanciaRecorrida(): int
    {
        $min = $this->vehiculo->cargas()->min('odometro');
        $max = $this->vehiculo->cargas()->max('odometro');

        return ($min !== null && $max !== null) ? (int) ($max - $min) : 0;
    }

    // ---- Money (currency-aware) --------------------------------------------

    public function totalCombustible(): float
    {
        return $this->sum('cargas', 'costo_total');
    }

    public function totalMantenimiento(): float
    {
        return $this->sum('mantenimientos', 'costo');
    }

    public function totalGastos(): float
    {
        return $this->sum('gastos', 'monto');
    }

    public function totalGeneral(): float
    {
        return round($this->totalCombustible() + $this->totalMantenimiento() + $this->totalGastos(), 2);
    }

    public function costoPorKm(): ?float
    {
        $dist = $this->distanciaRecorrida();
        return $dist > 0 ? round($this->totalGeneral() / $dist, 2) : null;
    }

    public function precioLitroPromedio(): ?float
    {
        $litros = (float) $this->vehiculo->cargas()->sum('litros');

        return $litros > 0 ? round($this->totalCombustible() / $litros, 2) : null;
    }

    /** Spending grouped by expense category (descending), in active currency. */
    public function gastosPorCategoria(): array
    {
        [$expr, $bind] = $this->montoExpr('monto');

        return $this->vehiculo->gastos()
            ->selectRaw("categoria, {$expr} as total", $bind)
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->pluck('total', 'categoria')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
    }

    /** Combined monthly spend for the last N months (oldest first). */
    public function gastoMensual(int $meses = 6): array
    {
        $desde = Carbon::now()->startOfMonth()->subMonths($meses - 1);

        $series = [];
        for ($i = 0; $i < $meses; $i++) {
            $series[(clone $desde)->addMonths($i)->format('Y-m')] = 0.0;
        }

        $fuentes = [
            [$this->vehiculo->cargas(), 'costo_total'],
            [$this->vehiculo->mantenimientos(), 'costo'],
            [$this->vehiculo->gastos(), 'monto'],
        ];

        foreach ($fuentes as [$query, $col]) {
            $rows = $query->where('fecha', '>=', $desde->toDateString())
                ->get(['fecha', $col, 'usd_rate']);

            foreach ($rows as $row) {
                $key = Carbon::parse($row->fecha)->format('Y-m');
                if (isset($series[$key])) {
                    $series[$key] += $this->convertir((float) $row->{$col}, $row->usd_rate);
                }
            }
        }

        return array_map(fn ($v) => round($v, 2), $series);
    }

    // ---- Internals ----------------------------------------------------------

    /** Sum a column over a relation in the active currency. */
    private function sum(string $relation, string $col): float
    {
        [$expr, $bind] = $this->montoExpr($col);

        return round((float) $this->vehiculo->{$relation}()
            ->selectRaw("{$expr} as agg", $bind)->value('agg'), 2);
    }

    /** SQL aggregate expression (+ bindings) for the active currency. */
    private function montoExpr(string $col): array
    {
        if ($this->moneda !== 'USD') {
            return ["COALESCE(SUM({$col}), 0)", []];
        }

        $fb = ($this->fallbackRate && $this->fallbackRate > 0) ? $this->fallbackRate : null;

        if ($fb === null) {
            // No current-rate fallback: only count rows that have a snapshot.
            return ["COALESCE(SUM(CASE WHEN usd_rate > 0 THEN {$col} / usd_rate ELSE 0 END), 0)", []];
        }

        return ["COALESCE(SUM({$col} / COALESCE(NULLIF(usd_rate, 0), ?)), 0)", [$fb]];
    }

    /** Convert a single ARS amount per the active currency. */
    private function convertir(float $ars, $rate): float
    {
        if ($this->moneda !== 'USD') {
            return $ars;
        }

        $r = ($rate && $rate > 0) ? (float) $rate : $this->fallbackRate;

        return ($r && $r > 0) ? $ars / $r : 0.0;
    }
}
