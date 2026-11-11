<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE solicitudes ALTER COLUMN proveido TYPE text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE solicitudes ALTER COLUMN proveido TYPE varchar(100)');
    }
};