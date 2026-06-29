<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Services\VehiculoStats;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var Vehiculo|null $vehiculo */
        $vehiculo = $request->attributes->get('vehiculo');

        if (! $vehiculo) {
            return view('dashboard', ['vehiculo' => null]);
        }

        $stats = VehiculoStats::for(
            $vehiculo,
            $request->attributes->get('moneda', 'ARS'),
            $request->attributes->get('usd_tipo', 'blue'),
            $request->attributes->get('usd_actual'),
        );

        $ultimosMantenimientos = $vehiculo->mantenimientos()
            ->orderByDesc('fecha')->limit(5)->get();

        $ultimosGastos = $vehiculo->gastos()
            ->orderByDesc('fecha')->limit(5)->get();

        return view('dashboard', [
            'vehiculo' => $vehiculo,
            'stats' => $stats,
            'gastosPorCategoria' => $stats->gastosPorCategoria(),
            'gastoMensual' => $stats->gastoMensual(6),
            'ultimosMantenimientos' => $ultimosMantenimientos,
            'ultimosGastos' => $ultimosGastos,
        ]);
    }
}
