<?php

namespace App\Models\Concerns;

use App\Services\ExchangeRateService;

/**
 * Shared USD conversion for money-bearing models. Each model implements
 * montoArs() returning its peso amount. On save we snapshot the ARS/USD rate
 * (blue and oficial) for the record's date, so its USD value is historical and
 * permanent. Failures degrade to null silently.
 */
trait ConvertibleAUsd
{
    /** Columns holding the snapshotted rate per quote. */
    private const USD_COLS = ['blue' => 'usd_blue', 'oficial' => 'usd_oficial'];

    /** Peso amount of this record. */
    abstract public function montoArs(): float;

    public static function bootConvertibleAUsd(): void
    {
        static::saving(function ($model) {
            if (app()->runningUnitTests() || empty($model->fecha)) {
                return;
            }

            $dateChanged = $model->exists && $model->isDirty('fecha');

            foreach (self::USD_COLS as $tipo => $col) {
                if (empty($model->{$col}) || $dateChanged) {
                    $rate = app(ExchangeRateService::class)->forDate($model->fecha, $tipo);
                    if ($rate) {
                        $model->{$col} = $rate;
                    }
                }
            }
        });
    }

    /** Stored rate for a quote ('blue' | 'oficial'), or null. */
    public function usdRate(string $tipo): ?float
    {
        $col = self::USD_COLS[$tipo] ?? 'usd_blue';

        return $this->{$col} ? (float) $this->{$col} : null;
    }

    /**
     * USD value using this record's own historical rate for the given quote,
     * or a supplied fallback (e.g. the current rate) when there's no snapshot.
     */
    public function montoUsd(string $tipo = 'blue', ?float $fallbackRate = null): ?float
    {
        $rate = $this->usdRate($tipo) ?: $fallbackRate;

        return ($rate && $rate > 0) ? round($this->montoArs() / $rate, 2) : null;
    }
}
