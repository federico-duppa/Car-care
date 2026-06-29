<?php

namespace App\Models\Concerns;

use App\Services\ExchangeRateService;

/**
 * Shared USD conversion for money-bearing models. Each model must implement
 * montoArs() returning its peso amount; the stored usd_rate is the ARS/USD
 * rate captured on the record's date.
 *
 * On save, the rate for the record's date is fetched and snapshotted, so the
 * USD value is historical and permanent. Failures degrade to null silently.
 */
trait ConvertibleAUsd
{
    /** Peso amount of this record. */
    abstract public function montoArs(): float;

    public static function bootConvertibleAUsd(): void
    {
        static::saving(function ($model) {
            if (! config('carcare.usd_enabled') || empty($model->fecha)) {
                return;
            }

            // Snapshot when missing, or refresh if the date changed on an
            // existing record (on insert, 'fecha' is always dirty).
            if (empty($model->usd_rate) || ($model->exists && $model->isDirty('fecha'))) {
                $rate = app(ExchangeRateService::class)->forDate($model->fecha);
                if ($rate) {
                    $model->usd_rate = $rate;
                }
            }
        });
    }

    /**
     * USD value using the record's own historical rate, or a supplied
     * fallback (e.g. the current rate) for records without a snapshot.
     */
    public function montoUsd(?float $fallbackRate = null): ?float
    {
        $rate = $this->usd_rate ?: $fallbackRate;

        return ($rate && $rate > 0) ? round($this->montoArs() / $rate, 2) : null;
    }
}
