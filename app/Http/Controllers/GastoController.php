<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        $gastos = $vehiculo->gastos()->orderByDesc('fecha')->paginate(25);

        return view('gastos.index', compact('vehiculo', 'gastos'));
    }

    public function create(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        return view('gastos.form', [
            'vehiculo' => $vehiculo,
            'gasto' => new Gasto([
                'fecha' => now()->toDateString(),
                'categoria' => 'seguro',
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
        $vehiculo->gastos()->create($data);

        return redirect()->route('gastos.index')->with('status', 'Gasto registrado.');
    }

    public function edit(Request $request, Gasto $gasto)
    {
        $this->authorizeRecord($request, $gasto);

        return view('gastos.form', [
            'vehiculo' => $gasto->vehiculo,
            'gasto' => $gasto,
        ]);
    }

    public function update(Request $request, Gasto $gasto)
    {
        $this->authorizeRecord($request, $gasto);
        $gasto->update($this->validated($request));

        return redirect()->route('gastos.index')->with('status', 'Gasto actualizado.');
    }

    public function destroy(Request $request, Gasto $gasto)
    {
        $this->authorizeRecord($request, $gasto);
        $gasto->delete();

        return redirect()->route('gastos.index')->with('status', 'Gasto eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'fecha' => ['required', 'date'],
            'categoria' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'recurrente' => ['nullable', 'boolean'],
        ]) + ['recurrente' => $request->boolean('recurrente')];
    }

    private function vehiculo(Request $request): ?Vehiculo
    {
        return $request->attributes->get('vehiculo');
    }

    private function authorizeRecord(Request $request, Gasto $gasto): void
    {
        abort_unless($gasto->user_id === $request->user()->id, 403);
    }

    private function sinVehiculo()
    {
        return redirect()->route('vehiculos.create')
            ->with('status', 'Primero agregá un vehículo.');
    }
}
