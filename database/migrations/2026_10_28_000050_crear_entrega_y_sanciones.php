<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficios', function (Blueprint $table) {   // RF25
            $table->id();
            $table->text('descripcion');
            $table->date('fecha')->useCurrent();
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('persona_id');
        });

        Schema::create('actas_entrega', function (Blueprint $table) {   // RF21: una acta por reserva
            $table->id();
            $table->timestampTz('fecha')->useCurrent();
            $table->boolean('compromiso_buen_uso')->default(false);
            $table->boolean('firmada')->default(false);
            $table->timestampTz('fecha_devolucion')->nullable();          // RF22
            $table->foreignId('reserva_id')->unique()->constrained('reservas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('detalle_actas', function (Blueprint $table) {   // ítems entregados
            $table->id();
            $table->integer('cantidad');
            $table->string('estado_entrega', 20);
            $table->string('estado_devolucion', 20)->nullable();
            $table->foreignId('acta_entrega_id')->constrained('actas_entrega')->restrictOnDelete();
            $table->foreignId('equipamiento_predio_id')->constrained('equipamiento_predios')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['acta_entrega_id', 'equipamiento_predio_id']);
            $table->index('equipamiento_predio_id');
        });

        Schema::create('personal_limpieza', function (Blueprint $table) {   // RF23
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('celular', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('limpiezas', function (Blueprint $table) {   // una limpieza por reserva
            $table->id();
            $table->date('fecha')->useCurrent();
            $table->string('estado', 20)->default('asignada');
            $table->foreignId('reserva_id')->unique()->constrained('reservas')->restrictOnDelete();
            $table->foreignId('personal_limpieza_id')->constrained('personal_limpieza')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('personal_limpieza_id');
            $table->index('fecha');
        });

        Schema::create('incidentes', function (Blueprint $table) {   // RF22: daños y responsables
            $table->id();
            $table->text('descripcion');
            $table->timestampTz('fecha')->useCurrent();
            $table->foreignId('acta_entrega_id')->constrained('actas_entrega')->restrictOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('acta_entrega_id');
            $table->index('persona_id');
        });

        Schema::create('sanciones', function (Blueprint $table) {   // RF24
            $table->id();
            $table->text('motivo');
            $table->date('fecha')->useCurrent();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->foreignId('reserva_id')->constrained('reservas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('persona_id');
            $table->index('reserva_id');
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE actas_entrega ADD CONSTRAINT ck_actas_devolucion
                CHECK (fecha_devolucion IS NULL OR fecha_devolucion >= fecha);
            ALTER TABLE actas_entrega ADD CONSTRAINT ck_actas_firma
                CHECK (NOT firmada OR compromiso_buen_uso);

            ALTER TABLE detalle_actas ADD CONSTRAINT ck_detalle_cantidad
                CHECK (cantidad > 0);
            ALTER TABLE detalle_actas ADD CONSTRAINT ck_detalle_estado_entrega
                CHECK (estado_entrega IN ('bueno', 'regular', 'malo'));
            ALTER TABLE detalle_actas ADD CONSTRAINT ck_detalle_estado_devol
                CHECK (estado_devolucion IS NULL
                       OR estado_devolucion IN ('bueno', 'regular', 'malo', 'faltante'));

            ALTER TABLE personal_limpieza ADD CONSTRAINT ck_personal_celular
                CHECK (celular IS NULL OR celular ~ '^\+?[0-9 ]{7,20}$');

            ALTER TABLE limpiezas ADD CONSTRAINT ck_limpiezas_estado
                CHECK (estado IN ('asignada', 'realizada', 'cancelada'));

            ALTER TABLE incidentes ADD CONSTRAINT ck_incidentes_desc
                CHECK (btrim(descripcion) <> '');

            ALTER TABLE sanciones ADD CONSTRAINT ck_sanciones_estado
                CHECK (estado IN ('pendiente', 'cumplida', 'anulada'));
            ALTER TABLE sanciones ADD CONSTRAINT ck_sanciones_motivo
                CHECK (btrim(motivo) <> '');
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sanciones');
        Schema::dropIfExists('incidentes');
        Schema::dropIfExists('limpiezas');
        Schema::dropIfExists('personal_limpieza');
        Schema::dropIfExists('detalle_actas');
        Schema::dropIfExists('actas_entrega');
        Schema::dropIfExists('beneficios');
    }
};