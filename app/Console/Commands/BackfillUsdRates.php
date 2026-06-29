<?php

namespace App\Console\Commands;

use App\Models\CargaCombustible;
use App\Models\Gasto;
use App\Models\Mantenimiento;
use App\Services\ExchangeRateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rates:backfill')]
#[Description('Fill the historical USD/ARS rate for records that have no snapshot yet')]
class BackfillUsdRates extends Command
{
    public function handle(ExchangeRateService $rates): int
    {
        if (! config('carcare.usd_enabled')) {
            $this->warn('USD feature disabled (CARCARE_USD_ENABLED=false). Nothing to do.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ([CargaCombustible::class, Mantenimiento::class, Gasto::class] as $model) {
            $rows = $model::whereNull('usd_rate')->whereNotNull('fecha')->get();
            $this->info(class_basename($model).': '.$rows->count().' registros sin cotización.');

            foreach ($rows as $row) {
                $rate = $rates->forDate($row->fecha);
                if ($rate) {
                    $row->usd_rate = $rate;
                    $row->saveQuietly();
                    $total++;
                } else {
                    $this->warn("  Sin cotización para {$row->fecha->toDateString()} (id {$row->id}).");
                }
            }
        }

        $this->info("Listo. {$total} registros actualizados.");

        return self::SUCCESS;
    }
}
