<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    public function index(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        $mantenimientos = $vehiculo->mantenimientos()
            ->orderByDesc('fecha')->paginate(25);

        return view('mantenimientos.index', compact('vehiculo', 'mantenimientos'));
    }

    public function create(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        return view('mantenimientos.form', [
            'vehiculo' => $vehiculo,
            'mantenimiento' => new Mantenimiento([
                'fecha' => now()->toDateString(),
                'odometro' => $vehiculo->km_actual,
                'tipo' => 'aceite',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $vehiculo->mantenimientos()->create($data);

        if (! empty($data['odometro']) && $data['odometro'] > (int) $vehiculo->km_actual) {
            $vehiculo->update(['km_actual' => $data['odometro']]);
        }

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento registrado.');
    }

    public function edit(Request $request, Mantenimiento $mantenimiento)
    {
        $this->authorizeRecord($request, $mantenimiento);

        return view('mantenimientos.form', [
            'vehiculo' => $mantenimiento->vehiculo,
            'mantenimiento' => $mantenimiento,
        ]);
    }

    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $this->authorizeRecord($request, $mantenimiento);
        $mantenimiento->update($this->validated($request));

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento actualizado.');
    }

    public function destroy(Request $request, Mantenimiento $mantenimiento)
    {
        $this->authorizeRecord($request, $mantenimiento);
        $mantenimiento->delete();

        return redirect()->route('mantenimientos.index')->with('status', 'Mantenimiento eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'fecha' => ['required', 'date'],
            'odometro' => ['nullable', 'integer', 'min:0'],
            'tipo' => ['required', 'string', 'max:255'],
            'costo' => ['required', 'numeric', 'min:0'],
            'taller' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]);
    }

    private function vehiculo(Request $request): ?Vehiculo
    {
        return $request->attributes->get('vehiculo');
    }

    private function authorizeRecord(Request $request, Mantenimiento $m): void
    {
        abort_unless($m->user_id === $request->user()->id, 403);
    }

    private function sinVehiculo()
    {
        return redirect()->route('vehiculos.create')
            ->with('status', 'Primero agregá un vehículo.');
    }
}
