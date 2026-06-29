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
        Schema::create('carga_combustibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('odometro'); // km en el momento de la carga
            $table->decimal('litros', 8, 2);
            $table->decimal('costo_total', 12, 2);
            $table->boolean('tanque_lleno')->default(true);
            $table->string('estacion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_id', 'odometro']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carga_combustibles');
    }
};
