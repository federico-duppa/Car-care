<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * For authenticated requests, load the user's vehicles and resolve the
 * "active" one (from session, falling back to the first). Share both with
 * every view so the layout can render the vehicle switcher, and expose the
 * active vehicle on the request for controllers.
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
        }

        return $next($request);
    }
}
