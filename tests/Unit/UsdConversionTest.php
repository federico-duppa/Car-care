<?php

namespace Tests\Unit;

use App\Models\Gasto;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\VehiculoStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsdConversionTest extends TestCase
{
    use RefreshDatabase;

    private function vehiculo(): Vehiculo
    {
        $user = User::factory()->create();

        return Vehiculo::create(['user_id' => $user->id, 'marca' => 'T', 'modelo' => 'C']);
    }

    public function test_record_converts_with_its_own_historical_rate(): void
    {
        $v = $this->vehiculo();
        $gasto = $v->gastos()->create([
            'user_id' => $v->user_id, 'fecha' => '2025-01-01',
            'categoria' => 'seguro', 'monto' => 100000, 'usd_rate' => 1000,
        ]);

        // 100000 ARS / 1000 = 100 USD, regardless of today's rate.
        $this->assertSame(100.0, $gasto->montoUsd());
    }

    public function test_record_without_snapshot_uses_fallback_rate(): void
    {
        $v = $this->vehiculo();
        $gasto = $v->gastos()->create([
            'user_id' => $v->user_id, 'fecha' => '2025-01-01',
            'categoria' => 'peajes', 'monto' => 50000, 'usd_rate' => null,
        ]);

        $this->assertNull($gasto->montoUsd());          // no rate at all
        $this->assertSame(100.0, $gasto->montoUsd(500)); // fallback 500
    }

    public function test_usd_totals_sum_each_row_at_its_rate(): void
    {
        $v = $this->vehiculo();

        // Two fills, same 100 USD each but different peso amounts/rates.
        $v->cargas()->create(['user_id' => $v->user_id, 'fecha' => '2025-01-01', 'odometro' => 1000, 'litros' => 40, 'costo_total' => 100000, 'tanque_lleno' => true, 'usd_rate' => 1000]);
        $v->cargas()->create(['user_id' => $v->user_id, 'fecha' => '2025-06-01', 'odometro' => 1500, 'litros' => 40, 'costo_total' => 120000, 'tanque_lleno' => true, 'usd_rate' => 1200]);

        $ars = VehiculoStats::for($v, 'ARS');
        $usd = VehiculoStats::for($v, 'USD');

        $this->assertSame(220000.0, $ars->totalCombustible());
        $this->assertSame(200.0, $usd->totalCombustible());
    }

    public function test_usd_totals_fall_back_to_current_rate_for_unsnapshotted_rows(): void
    {
        $v = $this->vehiculo();
        $v->gastos()->create(['user_id' => $v->user_id, 'fecha' => '2025-01-01', 'categoria' => 'seguro', 'monto' => 90000, 'usd_rate' => null]);

        // fallback current rate = 900 => 100 USD
        $usd = VehiculoStats::for($v, 'USD', 900);
        $this->assertSame(100.0, $usd->totalGastos());

        // Without a fallback, unsnapshotted rows contribute 0.
        $usdNoFb = VehiculoStats::for($v, 'USD');
        $this->assertSame(0.0, $usdNoFb->totalGastos());
    }

    public function test_category_breakdown_in_usd(): void
    {
        $v = $this->vehiculo();
        $v->gastos()->create(['user_id' => $v->user_id, 'fecha' => '2025-01-01', 'categoria' => 'seguro', 'monto' => 100000, 'usd_rate' => 1000]);
        $v->gastos()->create(['user_id' => $v->user_id, 'fecha' => '2025-02-01', 'categoria' => 'peajes', 'monto' => 20000, 'usd_rate' => 1000]);

        $cats = VehiculoStats::for($v, 'USD')->gastosPorCategoria();
        $this->assertSame(100.0, $cats['seguro']);
        $this->assertSame(20.0, $cats['peajes']);
    }
}
