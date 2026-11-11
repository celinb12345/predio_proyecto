<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->integer('capacidad')->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->string('link_google_maps', 500)->nullable();
            $table->string('estado', 20)->default('disponible');
            $table->foreignId('tipo_predio_id')->constrained('tipo_predios')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('responsables')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('responsable_id');
        });

        Schema::create('galeria_predios', function (Blueprint $table) {   // RF04
            $table->id();
            $table->string('tipo', 20);
            $table->string('archivo', 255);                              // ruta relativa
            $table->string('descripcion', 255)->nullable();
            $table->foreignId('predio_id')->constrained('predios')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('predio_id');
        });

        Schema::create('equipamiento_predios', function (Blueprint $table) {   // RF06
            $table->id();
            $table->integer('cantidad');
            $table->string('estado', 20)->default('bueno');
            $table->foreignId('predio_id')->constrained('predios')->restrictOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['predio_id', 'equipo_id']);
            $table->index('equipo_id');
        });

        Schema::create('tarifas', function (Blueprint $table) {   // RF08
            $table->id();
            $table->decimal('monto', 10, 2);
            $table->string('resolucion', 100);                    // resolución del Consejo Facultativo
            $table->boolean('vigente')->default(true);
            $table->foreignId('predio_id')->constrained('predios')->restrictOnDelete();
            $table->foreignId('tipo_persona_id')->constrained('tipo_personas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('tipo_persona_id');
        });

        Schema::create('mantenimientos', function (Blueprint $table) {   // RF26
            $table->id();
            $table->date('fecha')->useCurrent();
            $table->text('descripcion');
            $table->decimal('costo', 10, 2)->default(0);
            $table->foreignId('predio_id')->constrained('predios')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('responsables')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['predio_id', 'fecha']);
            $table->index('responsable_id');
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE predios ADD CONSTRAINT ck_predios_capacidad
                CHECK (capacidad IS NULL OR capacidad > 0);
            ALTER TABLE predios ADD CONSTRAINT ck_predios_estado
                CHECK (estado IN ('disponible', 'en_mantenimiento', 'inactivo'));
            ALTER TABLE predios ADD CONSTRAINT ck_predios_maps
                CHECK (link_google_maps IS NULL OR link_google_maps ~ '^https://');

            ALTER TABLE galeria_predios ADD CONSTRAINT ck_galeria_tipo
                CHECK (tipo IN ('ambiente', 'entrada', 'otro'));
            ALTER TABLE galeria_predios ADD CONSTRAINT ck_galeria_archivo
                CHECK (archivo !~ '\.\.' AND archivo !~ '^/');

            ALTER TABLE equipamiento_predios ADD CONSTRAINT ck_equipamiento_cantidad
                CHECK (cantidad >= 0);
            ALTER TABLE equipamiento_predios ADD CONSTRAINT ck_equipamiento_estado
                CHECK (estado IN ('bueno', 'regular', 'malo', 'baja'));

            ALTER TABLE tarifas ADD CONSTRAINT ck_tarifas_monto
                CHECK (monto >= 0);
            -- Una sola tarifa vigente por predio y tipo de persona (RF08)
            CREATE UNIQUE INDEX uq_tarifas_vigente
                ON tarifas (predio_id, tipo_persona_id) WHERE vigente;

            ALTER TABLE mantenimientos ADD CONSTRAINT ck_mantenimientos_costo
                CHECK (costo >= 0);
            ALTER TABLE mantenimientos ADD CONSTRAINT ck_mantenimientos_desc
                CHECK (btrim(descripcion) <> '');
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('equipamiento_predios');
        Schema::dropIfExists('galeria_predios');
        Schema::dropIfExists('predios');
    }
};