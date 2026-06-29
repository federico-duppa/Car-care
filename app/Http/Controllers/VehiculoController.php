<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        $vehiculos = $request->user()->vehiculos()->orderBy('marca')->get();

        return view('vehiculos.index', compact('vehiculos'));
    }

    public function create()
    {
        return view('vehiculos.form', ['vehiculo' => new Vehiculo()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $vehiculo = $request->user()->vehiculos()->create($data);

        $request->session()->put('vehiculo_id', $vehiculo->id);

        return redirect()->route('dashboard')->with('status', 'Vehículo agregado.');
    }

    public function edit(Request $request, Vehiculo $vehiculo)
    {
        $this->authorizeVehiculo($request, $vehiculo);

        return view('vehiculos.form', compact('vehiculo'));
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        $this->authorizeVehiculo($request, $vehiculo);
        $vehiculo->update($this->validated($request));

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo actualizado.');
    }

    public function destroy(Request $request, Vehiculo $vehiculo)
    {
        $this->authorizeVehiculo($request, $vehiculo);
        $vehiculo->delete();

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo eliminado.');
    }

    /** Set the active vehicle used across the app. */
    public function activar(Request $request, Vehiculo $vehiculo)
    {
        $this->authorizeVehiculo($request, $vehiculo);
        $request->session()->put('vehiculo_id', $vehiculo->id);

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'anio' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'patente' => ['nullable', 'string', 'max:20'],
            'km_actual' => ['nullable', 'integer', 'min:0'],
            'notas' => ['nullable', 'string'],
        ]);
    }

    private function authorizeVehiculo(Request $request, Vehiculo $vehiculo): void
    {
        abort_unless($vehiculo->user_id === $request->user()->id, 403);
    }
}
