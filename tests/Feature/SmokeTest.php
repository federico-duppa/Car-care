<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_shows_google_button(): void
    {
        $this->get('/login')->assertOk()->assertSee('Continuar con Google');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_renders_for_user_without_vehicle(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Agregar mi auto');
    }

    public function test_all_index_pages_render(): void
    {
        $user = User::factory()->create();
        Vehiculo::create([
            'user_id' => $user->id, 'marca' => 'Toyota', 'modelo' => 'Corolla',
            'anio' => 2018, 'km_actual' => 50000,
        ]);

        foreach (['/dashboard', '/combustible', '/mantenimientos', '/gastos', '/vehiculos'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_user_can_create_fuel_fill(): void
    {
        $user = User::factory()->create();
        $vehiculo = Vehiculo::create([
            'user_id' => $user->id, 'marca' => 'VW', 'modelo' => 'Gol', 'km_actual' => 1000,
        ]);

        $this->actingAs($user)->post('/combustible', [
            'fecha' => '2026-01-10', 'odometro' => 1200, 'litros' => 40,
            'costo_total' => 60000, 'tanque_lleno' => '1',
        ])->assertRedirect('/combustible');

        $this->assertDatabaseHas('carga_combustibles', [
            'vehiculo_id' => $vehiculo->id, 'odometro' => 1200, 'user_id' => $user->id,
        ]);

        // km_actual should advance to the latest odometer reading.
        $this->assertSame(1200, $vehiculo->fresh()->km_actual);
    }

    public function test_user_cannot_touch_other_users_records(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $vehiculo = Vehiculo::create([
            'user_id' => $owner->id, 'marca' => 'Fiat', 'modelo' => 'Cronos',
        ]);

        $this->actingAs($intruder)
            ->get("/vehiculos/{$vehiculo->id}/edit")
            ->assertForbidden();
    }
}
