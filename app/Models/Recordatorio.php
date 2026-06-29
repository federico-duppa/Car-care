<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A forward-looking alert. `clase` selects which fields matter:
 *  - mantenimiento: intervalo_km / intervalo_meses + tipo (Mantenimiento::TIPOS).
 *    "Last done" is derived from the latest matching Mantenimiento (auto-advances).
 *  - documento: base_fecha is the expiry; intervalo_meses renews it.
 *  - gasto: linked Gasto + intervalo_meses; next due = gasto date + period.
 */
class Recordatorio extends Model
{
    protected $table = 'recordatorios';

    protected $fillable = [
        'user_id', 'vehiculo_id', 'clase', 'titulo', 'tipo',
        'intervalo_km', 'intervalo_meses', 'base_odometro', 'base_fecha',
        'numero', 'gasto_id', 'activo', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'base_fecha' => 'date',
            'intervalo_km' => 'integer',
            'intervalo_meses' => 'integer',
            'base_odometro' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /** Reminder classes. */
    public const CLASES = [
        'mantenimiento' => 'Mantenimiento',
        'documento' => 'Documento / Vencimiento',
        'gasto' => 'Gasto recurrente',
    ];

    /** Document subtypes (for clase = documento). */
    public const DOCUMENTOS = [
        'seguro' => 'Seguro',
        'patente' => 'Patente / Impuesto',
        'vtv' => 'VTV / Revisión técnica',
        'licencia' => 'Licencia de conducir',
        'otro' => 'Otro',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class);
    }
}
