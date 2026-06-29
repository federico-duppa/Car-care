<?php

namespace Tests\Unit;

use App\Models\Mantenimiento;
use App\Models\Recordatorio;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\RecordatorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecordatorioEstadoTest extends TestCase
{
    use RefreshDatabase;

    private RecordatorioService $service;

    private User $user;

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecordatorioService;
        $this->user = User::factory()->create();
        $this->vehiculo = Vehiculo::create([
            'user_id' => $this->user->id, 'marca' => 'T', 'modelo' => 'C', 'km_actual' => 0,
        ]);
    }

    private function rec(array $attrs): Recordatorio
    {
        return Recordatorio::create($attrs + [
            'user_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->id,
            'titulo' => 'X',
            'activo' => true,
        ]);
    }

    public function test_km_maintenance_uses_baseline_when_no_record(): void
    {
        $r = $this->rec(['clase' => 'mantenimiento', 'tipo' => 'aceite', 'intervalo_km' => 10000, 'base_odometro' => 0]);

        $e = $this->service->estado($r, 10500);

        $this->assertSame(10000, $e['proximoKm']);
        $this->assertSame(-500, $e['restanteKm']);
        $this->assertSame('vencido', $e['estado']);
    }

    public function test_km_maintenance_auto_advances_from_latest_record(): void
    {
        Mantenimiento::create([
            'user_id' => $this->user->id, 'vehiculo_id' => $this->vehiculo->id,
            'fecha' => '2026-01-01', 'tipo' => 'aceite', 'odometro' => 12000, 'costo' => 1,
        ]);

        $r = $this->rec(['clase' => 'mantenimiento', 'tipo' => 'aceite', 'intervalo_km' => 10000, 'base_odometro' => 0]);

        $e = $this->service->estado($r, 15000);

        $this->assertSame(22000, $e['proximoKm']); // 12000 anchor + 10000
        $this->assertSame('ok', $e['estado']);
    }

    public function test_km_maintenance_is_proximo_within_threshold(): void
    {
        $r = $this->rec(['clase' => 'mantenimiento', 'tipo' => 'frenos', 'intervalo_km' => 10000, 'base_odometro' => 0]);

        $e = $this->service->estado($r, 9700); // 300 km left, under the 500 default

        $this->assertSame('proximo', $e['estado']);
    }

    public function test_documento_overdue_soon_and_ok(): void
    {
        $hoy = Carbon::parse('2026-06-01');

        $vencido = $this->rec(['clase' => 'documento', 'tipo' => 'vtv', 'base_fecha' => '2026-05-20']);
        $this->assertSame('vencido', $this->service->estado($vencido, null, $hoy)['estado']);

        $proximo = $this->rec(['clase' => 'documento', 'tipo' => 'seguro', 'base_fecha' => '2026-06-10']);
        $this->assertSame('proximo', $this->service->estado($proximo, null, $hoy)['estado']);

        $ok = $this->rec(['clase' => 'documento', 'tipo' => 'patente', 'base_fecha' => '2026-12-01']);
        $this->assertSame('ok', $this->service->estado($ok, null, $hoy)['estado']);
    }

    public function test_gasto_uses_period_from_linked_expense(): void
    {
        $hoy = Carbon::parse('2026-06-01');

        $gasto = $this->vehiculo->gastos()->create([
            'user_id' => $this->user->id, 'fecha' => '2026-05-10', 'categoria' => 'seguro',
            'monto' => 1000, 'recurrente' => true, 'periodicidad_meses' => 1,
        ]);

        $r = $this->rec([
            'clase' => 'gasto', 'tipo' => 'seguro', 'intervalo_meses' => 1,
            'gasto_id' => $gasto->id, 'base_fecha' => '2026-05-10',
        ]);

        $e = $this->service->estado($r, null, $hoy);

        $this->assertSame('2026-06-10', $e['proximaFecha']->toDateString());
        $this->assertSame('proximo', $e['estado']);
    }
}
