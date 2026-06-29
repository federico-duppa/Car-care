<?php

namespace Database\Seeders;

use App\Models\CargaCombustible;
use App\Models\Gasto;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed demo data. The demo user's email matches the first entry in
     * ALLOWED_EMAILS, so when you sign in with that Google account the
     * records below get linked to you automatically.
     */
    public function run(): void
    {
        $email = trim(explode(',', (string) config('services.allowed_emails'))[0] ?? '')
            ?: 'demo@example.com';

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Demo', 'email_verified_at' => now()]
        );

        $vehiculo = $user->vehiculos()->create([
            'marca' => 'Toyota', 'modelo' => 'Corolla', 'anio' => 2019,
            'patente' => 'AB123CD', 'km_actual' => 62000,
        ]);

        // Six monthly full-tank fills (~8.x L/100km).
        $odo = 50000;
        for ($i = 6; $i >= 1; $i--) {
            $odo += 550;
            $litros = 42 + $i;
            CargaCombustible::create([
                'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
                'fecha' => Carbon::now()->subMonths($i)->toDateString(),
                'odometro' => $odo, 'litros' => $litros,
                'costo_total' => $litros * 1100, 'tanque_lleno' => true,
                'estacion' => 'YPF',
                'usd_rate' => 1200 + (6 - $i) * 50, // dólar subiendo con el tiempo
            ]);
        }
        $vehiculo->update(['km_actual' => $odo]);

        Mantenimiento::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonths(2)->toDateString(),
            'odometro' => 58000, 'tipo' => 'aceite', 'costo' => 85000, 'taller' => 'Lubricentro',
            'usd_rate' => 1380,
        ]);
        Mantenimiento::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonths(5)->toDateString(),
            'odometro' => 54000, 'tipo' => 'neumaticos', 'costo' => 420000, 'taller' => 'Gomería Centro',
            'usd_rate' => 1250,
        ]);

        Gasto::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonth()->toDateString(),
            'categoria' => 'seguro', 'monto' => 55000, 'descripcion' => 'Cuota mensual', 'recurrente' => true,
            'usd_rate' => 1430,
        ]);
        Gasto::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonths(3)->toDateString(),
            'categoria' => 'impuestos', 'monto' => 90000, 'descripcion' => 'Patente cuota',
            'usd_rate' => 1330,
        ]);
        Gasto::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subDays(10)->toDateString(),
            'categoria' => 'peajes', 'monto' => 4800,
            'usd_rate' => 1460,
        ]);
    }
}
