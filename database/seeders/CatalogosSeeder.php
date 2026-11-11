<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $catalogos = [
            'roles' => ['Decano', 'Secretaría General', 'Director', 'Estudiante', 'Cliente'],
            'tipo_personas' => ['estudiante', 'director', 'autoridad', 'administrativo', 'particular', 'empresa'],
            'tipo_eventos' => ['Titulación', 'Congreso', 'Seminario', 'Taller', 'Otro'],
            'tipo_predios' => ['Coliseo', 'Cancha sintética', 'Paraninfo', 'Auditorio de posgrado', 'Aula'],
            'equipos' => ['Silla', 'Proyector', 'Computadora'],
        ];

        foreach ($catalogos as $tabla => $valores) {
            foreach ($valores as $descripcion) {
                DB::table($tabla)->insertOrIgnore([
                    'descripcion' => $descripcion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}