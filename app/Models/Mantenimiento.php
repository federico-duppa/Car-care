<?php

namespace App\Models;

use App\Models\Concerns\ConvertibleAUsd;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    use ConvertibleAUsd;

    protected $table = 'mantenimientos';

    protected $fillable = [
        'user_id', 'vehiculo_id', 'fecha', 'odometro', 'tipo',
        'costo', 'taller', 'notas', 'usd_blue', 'usd_oficial',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'costo' => 'decimal:2',
            'usd_blue' => 'decimal:4',
            'usd_oficial' => 'decimal:4',
        ];
    }

    public function montoArs(): float
    {
        return (float) $this->costo;
    }

    /** Tipos sugeridos de mantenimiento. */
    public const TIPOS = [
        'aceite' => 'Cambio de aceite',
        'filtros' => 'Filtros',
        'frenos' => 'Frenos',
        'neumaticos' => 'Neumáticos',
        'correa' => 'Correa de distribución',
        'bateria' => 'Batería',
        'service' => 'Service general',
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
}
