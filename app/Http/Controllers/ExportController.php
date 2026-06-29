<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Stream a CSV export of the active vehicle's records for the given type
     * (combustible | mantenimientos | gastos).
     */
    public function csv(Request $request, string $tipo): StreamedResponse
    {
        /** @var Vehiculo|null $vehiculo */
        $vehiculo = $request->attributes->get('vehiculo');
        abort_unless($vehiculo, 404);

        [$headers, $rows] = match ($tipo) {
            'combustible' => $this->combustible($vehiculo),
            'mantenimientos' => $this->mantenimientos($vehiculo),
            'gastos' => $this->gastos($vehiculo),
            default => abort(404),
        };

        $filename = "{$tipo}-{$vehiculo->id}.csv";

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function combustible(Vehiculo $vehiculo): array
    {
        $headers = ['fecha', 'odometro', 'litros', 'costo_total', 'precio_litro', 'usd_blue', 'usd_oficial', 'tanque_lleno', 'estacion', 'notas'];
        $rows = $vehiculo->cargas()->orderBy('fecha')->get()->map(fn ($c) => [
            $c->fecha->toDateString(), $c->odometro, $c->litros, $c->costo_total,
            $c->precio_litro, $c->usd_blue, $c->usd_oficial, $c->tanque_lleno ? 'si' : 'no', $c->estacion, $c->notas,
        ]);

        return [$headers, $rows];
    }

    private function mantenimientos(Vehiculo $vehiculo): array
    {
        $headers = ['fecha', 'odometro', 'tipo', 'costo', 'usd_blue', 'usd_oficial', 'taller', 'notas'];
        $rows = $vehiculo->mantenimientos()->orderBy('fecha')->get()->map(fn ($m) => [
            $m->fecha->toDateString(), $m->odometro, $m->tipo, $m->costo, $m->usd_blue, $m->usd_oficial, $m->taller, $m->notas,
        ]);

        return [$headers, $rows];
    }

    private function gastos(Vehiculo $vehiculo): array
    {
        $headers = ['fecha', 'categoria', 'monto', 'usd_blue', 'usd_oficial', 'descripcion', 'recurrente'];
        $rows = $vehiculo->gastos()->orderBy('fecha')->get()->map(fn ($g) => [
            $g->fecha->toDateString(), $g->categoria, $g->monto, $g->usd_blue, $g->usd_oficial, $g->descripcion, $g->recurrente ? 'si' : 'no',
        ]);

        return [$headers, $rows];
    }
}
