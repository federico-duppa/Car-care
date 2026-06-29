<?php

namespace Tests\Unit;

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

    public function test_record_converts_with_its_own_historical_rate_per_quote(): void
    {
        $v = $this->vehiculo();
        $gasto = $v->gastos()->create([
            'user_id' => $v->user_id, 'fecha' => '2025-01-01',
            'categoria' => 'seguro', 'monto' => 100000,
            'usd_blue' => 1000, 'usd_oficial' => 800,
        ]);

        $this->assertSame(100.0, $gasto->montoUsd('blue'));    // 100000 / 1000
        $this->assertSame(125.0, $gasto->montoUsd('oficial')); // 100000 / 800
    }

    public function test_record_without_snapshot_uses_fallback_rate(): void
    {
        $v = $this->vehiculo();
        $gasto = $v->gastos()->create([
            'user_id' => $v->user_id, 'fecha' => '2025-01-01',
            'categoria' => 'peajes', 'monto' => 50000,
        ]);

        $this->assertNull($gasto->montoUsd('blue'));        // no snapshot, no fallback
        $this->assertSame(100.0, $gasto->montoUsd('blue', 500)); // fallback 500
    }

    public function test_usd_totals_sum_each_row_at_its_rate(): void
    {
        $v = $this->vehiculo();

        $v->cargas()->create(['user_id' => $v->user_id, 'fecha' => '2025-01-01', 'odometro' => 1000, 'litros' => 40, 'costo_total' => 100000, 'tanque_lleno' => true, 'usd_blue' => 1000, 'usd_oficial' => 800]);
        $v->cargas()->create(['user_id' => $v->user_id, 'fecha' => '2025-06-01', 'odometro' => 1500, 'litros' => 40, 'costo_total' => 120000, 'tanque_lleno' => true, 'usd_blue' => 1200, 'usd_oficial' => 1000]);

        $this->assertSame(220000.0, VehiculoStats::for($v, 'ARS')->totalCombustible());
        $this->assertSame(200.0, VehiculoStats::for($v, 'USD', 'blue')->totalCombustible());
        // oficial: 100000/800 + 120000/1000 = 125 + 120 = 245
        $this->assertSame(245.0, VehiculoStats::for($v, 'USD', 'oficial')->totalCombustible());
    }

    public function test_usd_totals_fall_back_to_current_rate_for_unsnapshotted_rows(): void
    {
        $v = $this->vehiculo();
        $v->gastos()->create(['user_id' => $v->user_id, 'fecha' => '2025-01-01', 'categoria' => 'seguro', 'monto' => 90000]);

        $this->assertSame(100.0, VehiculoStats::for($v, 'USD', 'blue', 900)->totalGastos());
        $this->assertSame(0.0, VehiculoStats::for($v, 'USD', 'blue')->totalGastos());
    }

    public function test_category_breakdown_in_usd(): void
    {
        $v = $this->vehiculo();
        $v->gastos()->create(['user_id' => $v->user_id, 'fecha' => '2025-01-01', 'categoria' => 'seguro', 'monto' => 100000, 'usd_blue' => 1000]);
        $v->gastos()->create(['user_id' => $v->user_id, 'fecha' => '2025-02-01', 'categoria' => 'peajes', 'monto' => 20000, 'usd_blue' => 1000]);

        $cats = VehiculoStats::for($v, 'USD', 'blue')->gastosPorCategoria();
        $this->assertSame(100.0, $cats['seguro']);
        $this->assertSame(20.0, $cats['peajes']);
    }
}
