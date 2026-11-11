<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuotas', function (Blueprint $tabla) {
            $tabla->string('medio_pago', 20)->nullable();
            $tabla->string('qr_estado', 20)->nullable();
            $tabla->string('qr_id_empresa', 60)->nullable()->unique();
            $tabla->string('qr_id_transaccion', 60)->nullable()->index();
            $tabla->text('qr_imagen')->nullable();
            $tabla->timestampTz('qr_expira_en')->nullable();
            $tabla->timestampTz('qr_pagado_en')->nullable();
        });

        DB::statement("ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_medio CHECK (medio_pago IS NULL OR medio_pago IN ('efectivo', 'qr'))");
        DB::statement("ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_qr_estado CHECK (qr_estado IS NULL OR qr_estado IN ('pendiente', 'pagado', 'vencido', 'rechazado'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cuotas DROP CONSTRAINT IF EXISTS ck_cuotas_qr_estado');
        DB::statement('ALTER TABLE cuotas DROP CONSTRAINT IF EXISTS ck_cuotas_medio');

        Schema::table('cuotas', function (Blueprint $tabla) {
            $tabla->dropColumn([
                'medio_pago',
                'qr_estado',
                'qr_id_empresa',
                'qr_id_transaccion',
                'qr_imagen',
                'qr_expira_en',
                'qr_pagado_en',
            ]);
        });
    }
};