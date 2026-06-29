<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How often a recurring expense repeats (in months). Only meaningful when
     * `recurrente` is true; drives the linked `gasto` reminder. Nullable: a
     * plain recurring flag with no period just behaves like before.
     */
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->unsignedInteger('periodicidad_meses')->nullable()->after('recurrente');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('periodicidad_meses');
        });
    }
};
