<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// La tabla users ya la crea la migración por defecto de Laravel (0001_01_01_000000).
// Aquí solo agregamos los campos del diseño, para no crear la tabla dos veces.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto', 255)->nullable();
            $table->foreignId('persona_id')->nullable()->unique()
                  ->constrained('personas')->restrictOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('bloqueado_hasta')->nullable();
            $table->timestampTz('ultimo_acceso')->nullable();

            $table->index('role_id');
        });

        DB::unprepared(<<<'SQL'
            ALTER TABLE users ALTER COLUMN email TYPE citext;
            ALTER TABLE users ADD CONSTRAINT ck_users_email
                CHECK (email::text ~ '^[^@[:space:]]+@[^@[:space:]]+\.[^@[:space:]]+$');
            ALTER TABLE users ADD CONSTRAINT ck_users_foto
                CHECK (foto IS NULL OR (foto !~ '\.\.' AND foto !~ '^/'));
            ALTER TABLE users ADD CONSTRAINT ck_users_intentos
                CHECK (intentos_fallidos >= 0);
            SQL);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'foto', 'persona_id', 'role_id', 'activo',
                'intentos_fallidos', 'bloqueado_hasta', 'ultimo_acceso',
            ]);
        });
    }
};