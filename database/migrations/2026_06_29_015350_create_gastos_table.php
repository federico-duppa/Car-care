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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('categoria'); // impuestos, seguro, estacionamiento, multas, peajes, lavado, accesorios, otros
            $table->decimal('monto', 12, 2);
            $table->string('descripcion')->nullable();
            $table->boolean('recurrente')->default(false);
            $table->timestamps();

            $table->index(['vehiculo_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
