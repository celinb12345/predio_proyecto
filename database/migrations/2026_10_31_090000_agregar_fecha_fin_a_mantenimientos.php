<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mantenimientos', 'fecha_fin')) {
            Schema::table('mantenimientos', function (Blueprint $table) {
                $table->date('fecha_fin')->nullable()->after('fecha');
            });
        }

        // Los mantenimientos que ya existen duran un solo día: fecha fin = fecha
        DB::table('mantenimientos')
            ->whereNull('fecha_fin')
            ->update(['fecha_fin' => DB::raw('fecha')]);

        DB::statement(
            'ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS ck_mantenimientos_fechas'
        );
        DB::statement(
            'ALTER TABLE mantenimientos ADD CONSTRAINT ck_mantenimientos_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha)'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE mantenimientos DROP CONSTRAINT IF EXISTS ck_mantenimientos_fechas');

        if (Schema::hasColumn('mantenimientos', 'fecha_fin')) {
            Schema::table('mantenimientos', function (Blueprint $table) {
                $table->dropColumn('fecha_fin');
            });
        }
    }
};