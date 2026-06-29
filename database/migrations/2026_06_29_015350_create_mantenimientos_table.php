<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('odometro')->nullable();
            $table->string('tipo'); // aceite, filtros, frenos, neumaticos, correa, bateria, otro
            $table->decimal('costo', 12, 2)->default(0);
            $table->string('taller')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
