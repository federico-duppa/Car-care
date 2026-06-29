<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Forward-looking alerts: maintenance due by km/date, document expirations
     * (insurance, plate, inspection) and recurring expenses. A single `clase`
     * discriminator drives which fields apply. Status (overdue/soon/ok) is
     * computed at render time from the vehicle's km and today's date — no job
     * needed — so nothing here is a snapshot of state.
     */
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('clase'); // mantenimiento, documento, gasto
            $table->string('titulo');
            $table->string('tipo')->nullable();          // maintenance/document/expense subtype
            $table->unsignedInteger('intervalo_km')->nullable();
            $table->unsignedInteger('intervalo_meses')->nullable();
            $table->unsignedInteger('base_odometro')->nullable(); // baseline before first matching record
            $table->date('base_fecha')->nullable();      // baseline / explicit expiry date
            $table->string('numero')->nullable();        // policy / document number
            $table->foreignId('gasto_id')->nullable()->constrained('gastos')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_id', 'clase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
