<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ARS per 1 USD on the expense date, snapshotted at save time for both the
     * blue and the official quote. Nullable: rows saved while the rate API was
     * unreachable simply have no snapshot and fall back to the current rate.
     */
    private array $tables = ['carga_combustibles', 'mantenimientos', 'gastos'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->decimal('usd_blue', 12, 4)->nullable()->after('id');
                $table->decimal('usd_oficial', 12, 4)->nullable()->after('usd_blue');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['usd_blue', 'usd_oficial']);
            });
        }
    }
};
