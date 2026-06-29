<?php

namespace Database\Seeders;

use App\Models\CargaCombustible;
use App\Models\Gasto;
use App\Models\Mantenimiento;
use App\Models\Recordatorio;
use App\Models\User;
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
                'usd_blue' => 1200 + (6 - $i) * 50,    // dólar subiendo con el tiempo
                'usd_oficial' => 1000 + (6 - $i) * 35,
            ]);
        }
        $vehiculo->update(['km_actual' => $odo]);

        Mantenimiento::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonths(2)->toDateString(),
            'odometro' => 58000, 'tipo' => 'aceite', 'costo' => 85000, 'taller' => 'Lubricentro',
            'usd_blue' => 1380, 'usd_oficial' => 1150,
        ]);
        Mantenimiento::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonths(5)->toDateString(),
            'odometro' => 54000, 'tipo' => 'neumaticos', 'costo' => 420000, 'taller' => 'Gomería Centro',
            'usd_blue' => 1250, 'usd_oficial' => 1080,
        ]);

        $seguro = Gasto::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonth()->toDateString(),
            'categoria' => 'seguro', 'monto' => 55000, 'descripcion' => 'Cuota mensual',
            'recurrente' => true, 'periodicidad_meses' => 1,
            'usd_blue' => 1430, 'usd_oficial' => 1190,
        ]);
        Gasto::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subMonths(3)->toDateString(),
            'categoria' => 'impuestos', 'monto' => 90000, 'descripcion' => 'Patente cuota',
            'usd_blue' => 1330, 'usd_oficial' => 1120,
        ]);
        Gasto::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id,
            'fecha' => Carbon::now()->subDays(10)->toDateString(),
            'categoria' => 'peajes', 'monto' => 4800,
            'usd_blue' => 1460, 'usd_oficial' => 1200,
        ]);

        // Reminders: maintenance by km, document expirations, recurring expense.
        Recordatorio::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'mantenimiento',
            'titulo' => 'Cambio de aceite', 'tipo' => 'aceite',
            'intervalo_km' => 10000, 'intervalo_meses' => 12, 'base_odometro' => $odo - 9800,
        ]);
        Recordatorio::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'documento',
            'titulo' => 'VTV', 'tipo' => 'vtv', 'intervalo_meses' => 12,
            'base_fecha' => Carbon::now()->addDays(20)->toDateString(),
        ]);
        Recordatorio::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'documento',
            'titulo' => 'Seguro La Caja', 'tipo' => 'seguro', 'numero' => 'POL-99821', 'intervalo_meses' => 12,
            'base_fecha' => Carbon::now()->subDays(4)->toDateString(),
        ]);
        Recordatorio::create([
            'user_id' => $user->id, 'vehiculo_id' => $vehiculo->id, 'clase' => 'gasto',
            'titulo' => 'Seguro · Cuota mensual', 'tipo' => 'seguro', 'intervalo_meses' => 1,
            'gasto_id' => $seguro->id, 'base_fecha' => $seguro->fecha->toDateString(),
        ]);
    }
}
