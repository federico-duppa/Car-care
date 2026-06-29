<?php

namespace App\Models;

use App\Models\Concerns\ConvertibleAUsd;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargaCombustible extends Model
{
    use ConvertibleAUsd;

    protected $table = 'carga_combustibles';

    protected $fillable = [
        'user_id', 'vehiculo_id', 'fecha', 'odometro', 'litros',
        'costo_total', 'tanque_lleno', 'estacion', 'notas', 'usd_blue', 'usd_oficial',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'litros' => 'decimal:2',
            'costo_total' => 'decimal:2',
            'tanque_lleno' => 'boolean',
            'usd_blue' => 'decimal:4',
            'usd_oficial' => 'decimal:4',
        ];
    }

    public function montoArs(): float
    {
        return (float) $this->costo_total;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /** Precio por litro de esta carga. */
    public function getPrecioLitroAttribute(): ?float
    {
        return $this->litros > 0 ? round($this->costo_total / $this->litros, 2) : null;
    }
}
