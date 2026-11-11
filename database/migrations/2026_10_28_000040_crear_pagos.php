<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {   // RF19: un pago por reserva
            $table->id();
            $table->string('forma_pago', 20);
            $table->decimal('monto_total', 10, 2);
            $table->timestampTz('fecha')->useCurrent();
            $table->string('nro_comprobante', 50)->nullable();
            $table->string('comprobante', 255)->nullable();                   // ruta relativa
            $table->foreignId('reserva_id')->unique()->constrained('reservas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('cuotas', function (Blueprint $table) {   // solo si forma_pago = credito_2_cuotas
            $table->id();
            $table->smallInteger('nro_cuota');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_vencimiento');
            $table->timestampTz('fecha_pago')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('nro_comprobante', 50)->nullable();
            $table->string('comprobante', 255)->nullable();
            $table->foreignId('pago_id')->constrained('pagos')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['pago_id', 'nro_cuota']);
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE pagos ADD CONSTRAINT ck_pagos_forma
                CHECK (forma_pago IN ('contado', 'credito_2_cuotas'));
            ALTER TABLE pagos ADD CONSTRAINT ck_pagos_monto
                CHECK (monto_total > 0);
            ALTER TABLE pagos ADD CONSTRAINT ck_pagos_comprobante
                CHECK (comprobante IS NULL OR (comprobante !~ '\.\.' AND comprobante !~ '^/'));

            ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_nro
                CHECK (nro_cuota BETWEEN 1 AND 2);      -- ajustar si cambia la política
            ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_monto
                CHECK (monto > 0);
            ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_estado
                CHECK (estado IN ('pendiente', 'pagada', 'vencida'));
            -- Una cuota está 'pagada' si y solo si tiene fecha de pago
            ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_pagada
                CHECK ((estado = 'pagada') = (fecha_pago IS NOT NULL));
            ALTER TABLE cuotas ADD CONSTRAINT ck_cuotas_comprobante
                CHECK (comprobante IS NULL OR (comprobante !~ '\.\.' AND comprobante !~ '^/'));
            CREATE INDEX ix_cuotas_vencimiento ON cuotas (fecha_vencimiento) WHERE estado = 'pendiente';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('pagos');
    }
};