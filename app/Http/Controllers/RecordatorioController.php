<?php

namespace App\Http\Controllers;

use App\Models\Recordatorio;
use App\Models\Vehiculo;
use App\Services\RecordatorioService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecordatorioController extends Controller
{
    public function __construct(private RecordatorioService $service) {}

    public function index(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        $recordatorios = $this->service->avisos($vehiculo);

        return view('recordatorios.index', compact('vehiculo', 'recordatorios'));
    }

    public function create(Request $request)
    {
        $vehiculo = $this->vehiculo($request);
        if (! $vehiculo) {
            return $this->sinVehiculo();
        }

        return view('recordatorios.form', [
            'vehiculo' => $vehiculo,
            'recordatorio' => new Recordatorio([
                'clase' => $request->input('clase', 'mantenimiento'),
                'tipo' => 'aceite',
                'base_odometro' => $vehiculo->km_actual,
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
        $vehiculo->recordatorios()->create($data);

        return redirect()->route('recordatorios.index')->with('status', 'Recordatorio creado.');
    }

    public function edit(Request $request, Recordatorio $recordatorio)
    {
        $this->authorizeRecord($request, $recordatorio);

        return view('recordatorios.form', [
            'vehiculo' => $recordatorio->vehiculo,
            'recordatorio' => $recordatorio,
        ]);
    }

    public function update(Request $request, Recordatorio $recordatorio)
    {
        $this->authorizeRecord($request, $recordatorio);
        $recordatorio->update($this->validated($request));

        return redirect()->route('recordatorios.index')->with('status', 'Recordatorio actualizado.');
    }

    public function destroy(Request $request, Recordatorio $recordatorio)
    {
        $this->authorizeRecord($request, $recordatorio);
        $recordatorio->delete();

        return redirect()->route('recordatorios.index')->with('status', 'Recordatorio eliminado.');
    }

    /**
     * Act on a reminder: log the recurring expense again (gasto) or renew the
     * document (documento). Maintenance reminders auto-advance when you log the
     * maintenance, so their button just links to the maintenance form instead.
     */
    public function resolver(Request $request, Recordatorio $recordatorio)
    {
        $this->authorizeRecord($request, $recordatorio);

        if ($recordatorio->clase === 'gasto' && $recordatorio->gasto) {
            $orig = $recordatorio->gasto;
            $clone = $orig->replicate(['usd_blue', 'usd_oficial']);
            $clone->fecha = now()->toDateString();
            $clone->save(); // re-snapshots the USD rate for today's date

            $orig->update(['recurrente' => false]); // superseded; keeps the stream single
            $recordatorio->update(['gasto_id' => $clone->id, 'base_fecha' => $clone->fecha]);

            return redirect()->route('recordatorios.index')->with('status', 'Gasto registrado de nuevo.');
        }

        if ($recordatorio->clase === 'documento') {
            $base = $recordatorio->intervalo_meses
                ? now()->addMonths((int) $recordatorio->intervalo_meses)->toDateString()
                : now()->toDateString();
            $recordatorio->update(['base_fecha' => $base]);

            return redirect()->route('recordatorios.index')->with('status', 'Vencimiento renovado.');
        }

        // Maintenance: send the user to log it; the reminder advances on save.
        return redirect()->route('mantenimientos.create', ['tipo' => $recordatorio->tipo]);
    }

    private function validated(Request $request): array
    {
        $clase = $request->input('clase');

        $rules = [
            'clase' => ['required', Rule::in(array_keys(Recordatorio::CLASES))],
            'titulo' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:255'],
            'intervalo_km' => ['nullable', 'integer', 'min:1'],
            'intervalo_meses' => ['nullable', 'integer', 'min:1'],
            'base_odometro' => ['nullable', 'integer', 'min:0'],
            'base_fecha' => ['nullable', 'date'],
            'numero' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ];

        if ($clase === 'mantenimiento') {
            // Need at least one interval to know when it is due.
            $rules['intervalo_km'][] = 'required_without:intervalo_meses';
            $rules['intervalo_meses'][] = 'required_without:intervalo_km';
        }

        if ($clase === 'documento') {
            $rules['base_fecha'] = ['required', 'date'];
        }

        $data = $request->validate($rules);
        $data['activo'] = true;

        return $data;
    }

    private function vehiculo(Request $request): ?Vehiculo
    {
        return $request->attributes->get('vehiculo');
    }

    private function authorizeRecord(Request $request, Recordatorio $recordatorio): void
    {
        abort_unless($recordatorio->user_id === $request->user()->id, 403);
    }

    private function sinVehiculo()
    {
        return redirect()->route('vehiculos.create')
            ->with('status', 'Primero agregá un vehículo.');
    }
}
