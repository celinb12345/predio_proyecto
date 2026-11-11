<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioDecanoSeeder extends Seeder
{
    public function run(): void
    {
        $tipoAdministrativo = DB::table('tipo_personas')->where('descripcion', 'administrativo')->value('id');
        $rolDecano = DB::table('roles')->where('descripcion', 'Decano')->value('id');

        $personaId = DB::table('personas')->where('ci', '1234567')->value('id');
        if (! $personaId) {
            $personaId = DB::table('personas')->insertGetId([
                'nombre' => 'Decano',
                'apellido' => 'de Prueba',
                'ci' => '1234567',
                'celular' => '70000000',
                'tipo_persona_id' => $tipoAdministrativo,
            ]);
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'decano@predios.test'],
            [
                'name' => 'Decano de Prueba',
                'password' => Hash::make('secreto123'),
                'persona_id' => $personaId,
                'role_id' => $rolDecano,
                'activo' => true,
                'intentos_fallidos' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}