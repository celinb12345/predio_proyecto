<?php

namespace App\Support;

class CategoriasPersona
{
    /**
     * Jerarquía para la liberación de fechas y el historial.
     * Menor número = aparece primero. Lo que no esté en la lista va al final.
     * La clave debe coincidir con la descripción de tipo_personas en minúsculas.
     */
    public const ORDEN = [
        'autoridad' => 1,
        'director' => 2,
        'administrativo' => 3,
        'particular' => 4,
        'estudiante' => 5,
        'empresa' => 6,
    ];

    public const ORDEN_POR_DEFECTO = 99;

    public static function clave(?string $descripcion): string
    {
        return mb_strtolower(trim((string) $descripcion));
    }

    public static function orden(?string $descripcion): int
    {
        return self::ORDEN[self::clave($descripcion)] ?? self::ORDEN_POR_DEFECTO;
    }

    public static function etiqueta(?string $descripcion): string
    {
        $texto = trim((string) $descripcion);

        return $texto === '' ? 'Sin categoría' : mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }
}