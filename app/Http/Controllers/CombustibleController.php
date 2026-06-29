<?php

namespace App\Http\Controllers;

use App\Models\CargaCombustible;
use App\Models\Vehiculo;
use App\Services\VehiculoStats;
use Illuminate\Http\Request;

class CombustibleController extends Controller
{
    public function index(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        $cargas = $vehiculo->cargas()
            ->orderByDesc('fecha')->orderByDesc('odometro')
            ->paginate(25);

        $stats = VehiculoStats::for(
            $vehiculo,
            $request->attributes->get('moneda', 'ARS'),
            $request->attributes->get('usd_actual'),
        );

        return view('combustible.index', compact('vehiculo', 'cargas', 'stats'));
    }

    public function create(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        return view('combustible.form', [
            'vehiculo' => $vehiculo,
            'carga' => new CargaCombustible([
                'fecha' => now()->toDateString(),
                'odometro' => $vehiculo->km_actual,
                'tanque_lleno' => true,
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
        $vehiculo->cargas()->create($data);
        $this->actualizarKm($vehiculo, $data['odometro']);

        return redirect()->route('combustible.index')->with('status', 'Carga registrada.');
    }

    public function edit(Request $request, CargaCombustible $combustible)
    {
        $this->authorizeRecord($request, $combustible);

        return view('combustible.form', [
            'vehiculo' => $combustible->vehiculo,
            'carga' => $combustible,
        ]);
    }

    public function update(Request $request, CargaCombustible $combustible)
    {
        $this->authorizeRecord($request, $combustible);
        $data = $this->validated($request);
        $combustible->update($data);
        $this->actualizarKm($combustible->vehiculo, $data['odometro']);

        return redirect()->route('combustible.index')->with('status', 'Carga actualizada.');
    }

    public function destroy(Request $request, CargaCombustible $combustible)
    {
        $this->authorizeRecord($request, $combustible);
        $combustible->delete();

        return redirect()->route('combustible.index')->with('status', 'Carga eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'fecha' => ['required', 'date'],
            'odometro' => ['required', 'integer', 'min:0'],
            'litros' => ['required', 'numeric', 'min:0.01'],
            'costo_total' => ['required', 'numeric', 'min:0'],
            'tanque_lleno' => ['nullable', 'boolean'],
            'estacion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]) + ['tanque_lleno' => $request->boolean('tanque_lleno')];
    }

    private function actualizarKm(Vehiculo $vehiculo, int $odometro): void
    {
        if ($odometro > (int) $vehiculo->km_actual) {
            $vehiculo->update(['km_actual' => $odometro]);
        }
    }

    private function vehiculo(Request $request): ?Vehiculo
    {
        return $request->attributes->get('vehiculo');
    }

    private function authorizeRecord(Request $request, CargaCombustible $carga): void
    {
        abort_unless($carga->user_id === $request->user()->id, 403);
    }

    private function sinVehiculo()
    {
        return redirect()->route('vehiculos.create')
            ->with('status', 'Primero agregá un vehículo.');
    }
}
