<?php

use App\Http\Controllers\CombustibleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('vehiculos', VehiculoController::class)->except('show');
    Route::post('vehiculos/{vehiculo}/activar', [VehiculoController::class, 'activar'])
        ->name('vehiculos.activar');

    Route::resource('combustible', CombustibleController::class)->except('show');
    Route::resource('mantenimientos', MantenimientoController::class)->except('show');
    Route::resource('gastos', GastoController::class)->except('show');

    Route::get('export/{tipo}', [ExportController::class, 'csv'])->name('export.csv');

    // Toggle the display currency (ARS <-> USD), persisted in the session.
    Route::post('moneda', function (\Illuminate\Http\Request $request) {
        $moneda = $request->input('moneda') === 'USD' ? 'USD' : 'ARS';
        $request->session()->put('moneda', $moneda);

        return back();
    })->name('moneda.set');

    // Choose which USD quote to convert with (blue / oficial).
    Route::post('usd-tipo', function (\Illuminate\Http\Request $request) {
        $tipos = (array) config('carcare.usd_tipos', ['blue']);
        $tipo = in_array($request->input('tipo'), $tipos, true) ? $request->input('tipo') : ($tipos[0] ?? 'blue');
        $request->session()->put('usd_tipo', $tipo);
        $request->session()->put('moneda', 'USD'); // choosing a quote implies USD view

        return back();
    })->name('usd_tipo.set');
});

require __DIR__.'/auth.php';
