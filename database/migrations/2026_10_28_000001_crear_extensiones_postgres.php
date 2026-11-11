<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // citext: emails sin distinguir mayúsculas.
        // btree_gist: necesario para la restricción que evita cruces de reservas (RF14).
        DB::unprepared(<<<'SQL'
            CREATE EXTENSION IF NOT EXISTS citext;
            CREATE EXTENSION IF NOT EXISTS btree_gist;
            SQL);
    }

    public function down(): void
    {
        // Las extensiones se dejan instaladas: pueden usarlas otras tablas o bases.
    }
};