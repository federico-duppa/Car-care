<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ARS per 1 USD on the expense date (snapshot taken at save time).
     * Nullable: rows created before this feature, or saved while the rate
     * API was unreachable, simply have no snapshot and fall back to the
     * current rate when displayed in USD.
     */
    private array $tables = ['carga_combustibles', 'mantenimientos', 'gastos'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->decimal('usd_rate', 12, 4)->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('usd_rate');
            });
        }
    }
};
