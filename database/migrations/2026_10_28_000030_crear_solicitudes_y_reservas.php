<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {   // RF11
            $table->id();
            $table->string('referencia', 30)->unique();
            $table->string('proveido', 100)->nullable();              // RF12
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('tipo_evento_id')->constrained('tipo_eventos')->restrictOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('tipo_evento_id');
            $table->index('persona_id');
            $table->index('estado');
        });

        Schema::create('solicitud_predios', function (Blueprint $table) {   // predio + fecha + horario
            $table->id();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('activo')->default(true);                   // false cuando se libera
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->foreignId('predio_id')->constrained('predios')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('solicitud_id');
        });

        Schema::create('respaldos_solicitud', function (Blueprint $table) {   // RF12
            $table->id();
            $table->string('tipo', 30);
            $table->string('archivo', 255);
            $table->foreignId('solicitud_id')->constrained('solicitudes')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('solicitud_id');
        });

        Schema::create('reservas', function (Blueprint $table) {   // una reserva por solicitud
            $table->id();
            $table->string('estado', 20)->default('pendiente');
            $table->string('tipo_prestamo', 10)->default('cobrado');   // RF20
            $table->decimal('monto_total', 10, 2)->default(0);
            $table->foreignId('solicitud_id')->unique()->constrained('solicitudes')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('estado');
        });

        Schema::create('liberaciones_fecha', function (Blueprint $table) {   // RF16
            $table->id();
            $table->text('motivo');
            $table->timestampTz('fecha_liberacion')->useCurrent();
            $table->foreignId('solicitud_predio_id')->unique()
                  ->constrained('solicitud_predios')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();   // auditoría
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('usuario_id');
        });

        Schema::create('reprogramaciones', function (Blueprint $table) {   // RF17: historial
            $table->id();
            $table->text('motivo');
            $table->date('fecha_anterior');
            $table->time('hora_inicio_anterior');
            $table->time('hora_fin_anterior');
            $table->timestampTz('fecha_registro')->useCurrent();
            $table->foreignId('solicitud_predio_id')->constrained('solicitud_predios')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('solicitud_predio_id');
            $table->index('usuario_id');
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE solicitudes ADD CONSTRAINT ck_solicitudes_estado
                CHECK (estado IN ('pendiente', 'aprobada', 'rechazada', 'cancelada'));

            ALTER TABLE solicitud_predios ADD CONSTRAINT ck_sp_horas
                CHECK (hora_fin > hora_inicio);
            -- RF14: dos fechas activas del mismo predio no pueden cruzarse en fecha y horario
            ALTER TABLE solicitud_predios ADD CONSTRAINT ex_solicitud_predios_sin_cruces
                EXCLUDE USING gist (
                    predio_id WITH =,
                    tsrange(fecha + hora_inicio, fecha + hora_fin, '[)') WITH &&
                ) WHERE (activo);
            CREATE INDEX ix_sp_calendario ON solicitud_predios (predio_id, fecha) WHERE activo;

            ALTER TABLE respaldos_solicitud ADD CONSTRAINT ck_respaldos_tipo
                CHECK (tipo IN ('carta', 'proveido', 'titulo', 'acta', 'otro'));
            ALTER TABLE respaldos_solicitud ADD CONSTRAINT ck_respaldos_archivo
                CHECK (archivo !~ '\.\.' AND archivo !~ '^/');

            ALTER TABLE reservas ADD CONSTRAINT ck_reservas_estado
                CHECK (estado IN ('pendiente', 'confirmada', 'cancelada', 'finalizada'));
            ALTER TABLE reservas ADD CONSTRAINT ck_reservas_tipo
                CHECK (tipo_prestamo IN ('gratuito', 'cobrado'));
            ALTER TABLE reservas ADD CONSTRAINT ck_reservas_monto
                CHECK (monto_total >= 0);
            ALTER TABLE reservas ADD CONSTRAINT ck_reservas_gratuito
                CHECK (tipo_prestamo = 'cobrado' OR monto_total = 0);

            ALTER TABLE liberaciones_fecha ADD CONSTRAINT ck_liberaciones_motivo
                CHECK (btrim(motivo) <> '');

            ALTER TABLE reprogramaciones ADD CONSTRAINT ck_reprog_motivo
                CHECK (btrim(motivo) <> '');
            ALTER TABLE reprogramaciones ADD CONSTRAINT ck_reprog_horas
                CHECK (hora_fin_anterior > hora_inicio_anterior);
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reprogramaciones');
        Schema::dropIfExists('liberaciones_fecha');
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('respaldos_solicitud');
        Schema::dropIfExists('solicitud_predios');
        Schema::dropIfExists('solicitudes');
    }
};