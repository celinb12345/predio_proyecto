<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_personas', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 100)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 50)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('tipo_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 100)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('tipo_predios', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 100)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 150)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE tipo_personas ADD CONSTRAINT ck_tipo_personas_desc CHECK (btrim(descripcion) <> '');
            ALTER TABLE roles         ADD CONSTRAINT ck_roles_desc         CHECK (btrim(descripcion) <> '');
            ALTER TABLE tipo_eventos  ADD CONSTRAINT ck_tipo_eventos_desc  CHECK (btrim(descripcion) <> '');
            ALTER TABLE tipo_predios  ADD CONSTRAINT ck_tipo_predios_desc  CHECK (btrim(descripcion) <> '');
            ALTER TABLE equipos       ADD CONSTRAINT ck_equipos_desc       CHECK (btrim(descripcion) <> '');
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('tipo_predios');
        Schema::dropIfExists('tipo_eventos');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tipo_personas');
    }
};