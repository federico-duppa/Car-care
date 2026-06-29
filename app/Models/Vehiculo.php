<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'user_id', 'marca', 'modelo', 'anio', 'patente', 'km_actual', 'notas',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cargas(): HasMany
    {
        return $this->hasMany(CargaCombustible::class);
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function recordatorios(): HasMany
    {
        return $this->hasMany(Recordatorio::class);
    }

    public function getNombreAttribute(): string
    {
        return trim("{$this->marca} {$this->modelo}");
    }
}
