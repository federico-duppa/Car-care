<?php

namespace Tests\Unit;

use App\Models\CargaCombustible;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\VehiculoStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiculoStatsTest extends TestCase
{
    use RefreshDatabase;

    private function vehiculoConCargas(): Vehiculo
    {
        $user = User::factory()->create();
        $vehiculo = Vehiculo::create([
            'user_id' => $user->id, 'marca' => 'Test', 'modelo' => 'Car',
        ]);

        // First full tank is the reference (litres not counted).
        $fills = [
            ['odometro' => 1000, 'litros' => 45, 'costo_total' => 45000, 'tanque_lleno' => true],
            ['odometro' => 1500, 'litros' => 40, 'costo_total' => 40000, 'tanque_lleno' => true], // 500 km / 40 L => 8.0
            ['odometro' => 2000, 'litros' => 50, 'costo_total' => 55000, 'tanque_lleno' => true], // 500 km / 50 L => 10.0
        ];

        foreach ($fills as $f) {
            $vehiculo->cargas()->create($f + [
                'user_id' => $user->id, 'fecha' => '2026-01-01',
            ]);
        }

        return $vehiculo;
    }

    public function test_consumption_uses_full_to_full_method(): void
    {
        $stats = VehiculoStats::for($this->vehiculoConCargas());

        $intervalos = $stats->intervalosConsumo();
        $this->assertCount(2, $intervalos);
        $this->assertEquals(8.0, $intervalos[0]['l_100km']);
        $this->assertEquals(10.0, $intervalos[1]['l_100km']);

        // Average = (40 + 50) litres over (1000) km => 9.0 L/100km
        $this->assertEquals(9.0, $stats->consumoPromedioL100());
        $this->assertEquals(10.0, $stats->consumoUltimoL100());
    }

    public function test_distance_and_cost_per_km(): void
    {
        $vehiculo = $this->vehiculoConCargas();
        $stats = VehiculoStats::for($vehiculo);

        $this->assertSame(1000, $stats->distanciaRecorrida());

        // Total fuel spend = 45000 + 40000 + 55000 = 140000 over 1000 km
        $this->assertEquals(140000.0, $stats->totalCombustible());
        $this->assertEquals(140.0, $stats->costoPorKm());
    }

    public function test_partial_fill_is_accumulated_into_next_full(): void
    {
        $user = User::factory()->create();
        $vehiculo = Vehiculo::create(['user_id' => $user->id, 'marca' => 'P', 'modelo' => 'Q']);

        $vehiculo->cargas()->create(['user_id' => $user->id, 'fecha' => '2026-01-01', 'odometro' => 0, 'litros' => 30, 'costo_total' => 1, 'tanque_lleno' => true]);
        $vehiculo->cargas()->create(['user_id' => $user->id, 'fecha' => '2026-01-02', 'odometro' => 200, 'litros' => 10, 'costo_total' => 1, 'tanque_lleno' => false]);
        $vehiculo->cargas()->create(['user_id' => $user->id, 'fecha' => '2026-01-03', 'odometro' => 500, 'litros' => 30, 'costo_total' => 1, 'tanque_lleno' => true]);

        $stats = VehiculoStats::for($vehiculo);
        $intervalos = $stats->intervalosConsumo();

        // One interval over 500 km consuming 10 + 30 = 40 L => 8.0 L/100km
        $this->assertCount(1, $intervalos);
        $this->assertEquals(8.0, $intervalos[0]['l_100km']);
    }
}
