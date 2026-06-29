<?php

namespace App\Http\Middleware;

use App\Services\ExchangeRateService;
use App\Services\RecordatorioService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * For authenticated requests: load the user's vehicles + resolve the active
 * one, and resolve the active display currency (ARS/USD) plus the current
 * USD rate used as a fallback for records without a snapshot.
 */
class ShareVehiculos
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $vehiculos = $user->vehiculos()->orderBy('marca')->get();

            $activoId = $request->session()->get('vehiculo_id');
            $activo = $vehiculos->firstWhere('id', $activoId) ?? $vehiculos->first();

            if ($activo) {
                $request->session()->put('vehiculo_id', $activo->id);
            }

            $request->attributes->set('vehiculo', $activo);

            View::share('vehiculos', $vehiculos);
            View::share('vehiculoActivo', $activo);

            // Reminders due/overdue for the nav badge.
            View::share('avisosCount', $activo
                ? app(RecordatorioService::class)->contar($activo)
                : 0);

            // Currency toggle (ARS/USD) + quote selector (blue/oficial).
            $tipos = (array) config('carcare.usd_tipos', ['blue']);
            $moneda = $request->session()->get('moneda', 'ARS') === 'USD' ? 'USD' : 'ARS';
            $usdTipo = in_array($request->session()->get('usd_tipo'), $tipos, true)
                ? $request->session()->get('usd_tipo')
                : ($tipos[0] ?? 'blue');

            $usdActuales = app(ExchangeRateService::class)->currentAll();
            $usdActual = $usdActuales[$usdTipo] ?? null;

            // Expose the active current rate to the show_money() helper.
            config(['carcare.usd_actual' => $usdActual]);

            $request->attributes->set('moneda', $moneda);
            $request->attributes->set('usd_tipo', $usdTipo);
            $request->attributes->set('usd_actual', $usdActual);

            View::share('moneda', $moneda);
            View::share('usdTipo', $usdTipo);
            View::share('usdActual', $usdActual);
            View::share('usdActuales', $usdActuales);
        }

        return $next($request);
    }
}
