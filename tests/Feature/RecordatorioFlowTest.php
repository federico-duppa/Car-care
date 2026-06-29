<?php

namespace Tests\Feature;

use App\Models\Recordatorio;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordatorioFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Vehiculo} */
    private function userWithVehiculo(): array
    {
        $user = User::factory()->create();
        $vehiculo = Vehiculo::create([
            'user_id' => $user->id, 'marca' => 'T', 'modelo' => 'C', 'km_actual' => 10000,
        ]);

        return [$user, $vehiculo];
    }

    public function test_can_create_maintenance_reminder(): void
    {
        [$user, $vehiculo] = $this->userWithVehiculo();

        $this->actingAs($user)->post('/recordatorios', [
            'clase' => 'mantenimiento', 'titulo' => 'Aceite', 'tipo' => 'aceite',
            'intervalo_km' => 10000, 'base_odometro' => 0,
        ])->assertRedirect('/recordatorios');

        $this->assertDatabaseHas('recordatorios', [
            'vehiculo_id' => $vehiculo->id, 'clase' => 'mantenimiento', 'intervalo_km' => 10000,
        ]);
    }

    public function test_maintenance_reminder_requires_an_interval(): void
    {
        [$user] = $this->userWithVehiculo();

        $this->actingAs($user)->post('/recordatorios', [
            'clase' => 'mantenimiento', 'titulo' => 'Aceite', 'tipo' => 'aceite',
        ])->assertSessionHasErrors(['intervalo_km', 'intervalo_meses']);
    }

    public function test_recurring_expense_creates_and_removes_reminder(): void
    {
        [$user, $vehiculo] = $this->userWithVehiculo();

        $this->actingAs($user)->post('/gastos', [
            'fecha' => '2026-05-01', 'categoria' => 'seguro', 'monto' => 50000,
            'recurrente' => '1', 'periodicidad_meses' => 1,
        ])->assertRedirect('/gastos');

        $gasto = $vehiculo->gastos()->firstOrFail();
        $this->assertDatabaseHas('recordatorios', ['clase' => 'gasto', 'gasto_id' => $gasto->id]);

        // Unchecking recurrente removes the linked reminder.
        $this->actingAs($user)->put("/gastos/{$gasto->id}", [
            'fecha' => '2026-05-01', 'categoria' => 'seguro', 'monto' => 50000, 'recurrente' => '0',
        ])->assertRedirect('/gastos');

        $this->assertDatabaseMissing('recordatorios', ['gasto_id' => $gasto->id]);
    }

    public function test_resolver_repeats_a_recurring_expense(): void
    {
        [$user, $vehiculo] = $this->userWithVehiculo();

        $this->actingAs($user)->post('/gastos', [
            'fecha' => '2026-05-01', 'categoria' => 'seguro', 'monto' => 50000,
            'recurrente' => '1', 'periodicidad_meses' => 1,
        ]);

        $rec = Recordatorio::where('clase', 'gasto')->firstOrFail();
        $origId = $rec->gasto_id;

        $this->actingAs($user)->post("/recordatorios/{$rec->id}/resolver")
            ->assertRedirect('/recordatorios');

        $this->assertSame(2, $vehiculo->gastos()->count()); // original + clone
        $rec->refresh();
        $this->assertNotSame($origId, $rec->gasto_id);      // stream advanced to the clone
        $this->assertSame(now()->toDateString(), $rec->gasto->fecha->toDateString());
        $this->assertDatabaseHas('gastos', ['id' => $origId, 'recurrente' => false]);
    }

    public function test_resolver_renews_a_document(): void
    {
        [$user, $vehiculo] = $this->userWithVehiculo();

        $rec = Recordatorio::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'documento',
            'titulo' => 'VTV', 'tipo' => 'vtv', 'base_fecha' => '2026-01-01',
            'intervalo_meses' => 12, 'activo' => true,
        ]);

        $this->actingAs($user)->post("/recordatorios/{$rec->id}/resolver")
            ->assertRedirect('/recordatorios');

        $this->assertSame(now()->addMonths(12)->toDateString(), $rec->refresh()->base_fecha->toDateString());
    }

    public function test_reminder_views_and_dashboard_card_render(): void
    {
        [$user, $vehiculo] = $this->userWithVehiculo();

        // An overdue maintenance reminder so the dashboard card and badge show.
        $rec = Recordatorio::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'mantenimiento',
            'titulo' => 'Cambio de aceite', 'tipo' => 'aceite', 'intervalo_km' => 5000,
            'base_odometro' => 0, 'activo' => true, // due at 5000, km_actual is 10000 => overdue
        ]);

        $this->actingAs($user)->get('/recordatorios')->assertOk()->assertSee('Cambio de aceite');
        $this->actingAs($user)->get('/recordatorios/create')->assertOk();
        $this->actingAs($user)->get("/recordatorios/{$rec->id}/edit")->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('Próximos vencimientos')->assertSee('Vencido');
    }

    public function test_cannot_touch_another_users_reminder(): void
    {
        [$owner, $vehiculo] = $this->userWithVehiculo();
        $intruder = User::factory()->create();

        $rec = Recordatorio::create([
            'user_id' => $owner->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'documento',
            'titulo' => 'Seguro', 'tipo' => 'seguro', 'base_fecha' => '2026-01-01', 'activo' => true,
        ]);

        $this->actingAs($intruder)->get("/recordatorios/{$rec->id}/edit")->assertForbidden();
    }
}
