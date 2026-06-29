<?php

namespace App\Http\Middleware;

use App\Services\ExchangeRateService;
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

            // Currency toggle + current rate.
            $usdEnabled = (bool) config('carcare.usd_enabled');
            $moneda = $usdEnabled ? $request->session()->get('moneda', 'ARS') : 'ARS';
            $usdActual = $usdEnabled ? app(ExchangeRateService::class)->current() : null;

            // Expose the current rate to the show_money() helper.
            config(['carcare.usd_actual' => $usdActual]);

            $request->attributes->set('moneda', $moneda);
            $request->attributes->set('usd_actual', $usdActual);

            View::share('moneda', $moneda);
            View::share('usdActual', $usdActual);
            View::share('usdEnabled', $usdEnabled);
        }

        return $next($request);
    }
}
