<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'user_id', 'vehiculo_id', 'fecha', 'categoria',
        'monto', 'descripcion', 'recurrente',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
            'recurrente' => 'boolean',
        ];
    }

    /** Categorías de gasto. */
    public const CATEGORIAS = [
        'impuestos' => 'Impuestos / Patente',
        'seguro' => 'Seguro',
        'estacionamiento' => 'Estacionamiento',
        'multas' => 'Multas',
        'peajes' => 'Peajes',
        'lavado' => 'Lavado',
        'accesorios' => 'Accesorios',
        'otros' => 'Otros',
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
