<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsables', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('cargo', 100)->nullable();
            $table->string('celular', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);                       // para empresas: razón social
            $table->string('apellido', 100)->nullable();         // nulo para empresas
            $table->string('ci', 20)->nullable()->unique();      // nulo cuando no aplica
            $table->string('nit', 20)->nullable()->unique();     // solo empresas (RF09)
            $table->string('celular', 20)->nullable();
            $table->foreignId('tipo_persona_id')->constrained('tipo_personas')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('tipo_persona_id');
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE responsables ADD CONSTRAINT ck_responsables_celular
                CHECK (celular IS NULL OR celular ~ '^\+?[0-9 ]{7,20}$');
            ALTER TABLE personas ADD CONSTRAINT ck_personas_nombre
                CHECK (btrim(nombre) <> '');
            ALTER TABLE personas ADD CONSTRAINT ck_personas_ci
                CHECK (ci IS NULL OR ci ~ '^[0-9A-Za-z -]{5,20}$');
            ALTER TABLE personas ADD CONSTRAINT ck_personas_nit
                CHECK (nit IS NULL OR nit ~ '^[0-9]{7,20}$');
            ALTER TABLE personas ADD CONSTRAINT ck_personas_celular
                CHECK (celular IS NULL OR celular ~ '^\+?[0-9 ]{7,20}$');
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
        Schema::dropIfExists('responsables');
    }
};