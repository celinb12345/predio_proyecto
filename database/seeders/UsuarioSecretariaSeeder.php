<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSecretariaSeeder extends Seeder
{
    public function run(): void
    {
        $tipoAdministrativo = DB::table('tipo_personas')->where('descripcion', 'administrativo')->value('id');
        $rolSecretaria = DB::table('roles')->where('descripcion', 'Secretaría General')->value('id');

        $personaId = DB::table('personas')->where('ci', '7654321')->value('id');
        if (! $personaId) {
            $personaId = DB::table('personas')->insertGetId([
                'nombre' => 'Secretaria',
                'apellido' => 'General de Prueba',
                'ci' => '7654321',
                'celular' => '71111111',
                'tipo_persona_id' => $tipoAdministrativo,
            ]);
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'secretaria@predios.test'],
            [
                'name' => 'Secretaria General de Prueba',
                'password' => Hash::make('secreto123'),
                'persona_id' => $personaId,
                'role_id' => $rolSecretaria,
                'activo' => true,
                'intentos_fallidos' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}