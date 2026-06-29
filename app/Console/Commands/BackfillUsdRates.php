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
        $cols = ['blue' => 'usd_blue', 'oficial' => 'usd_oficial'];
        $total = 0;

        foreach ([CargaCombustible::class, Mantenimiento::class, Gasto::class] as $model) {
            $rows = $model::whereNotNull('fecha')
                ->where(function ($q) use ($cols) {
                    foreach ($cols as $col) {
                        $q->orWhereNull($col);
                    }
                })->get();

            $this->info(class_basename($model).': '.$rows->count().' registros sin cotización completa.');

            foreach ($rows as $row) {
                $changed = false;
                foreach ($cols as $tipo => $col) {
                    if (empty($row->{$col})) {
                        $rate = $rates->forDate($row->fecha, $tipo);
                        if ($rate) {
                            $row->{$col} = $rate;
                            $changed = true;
                        }
                    }
                }

                if ($changed) {
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
