<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AlmacenFotoService
{
    private const CARPETA = 'fotos_usuarios';
    private const DISCO = 'public';

    // Guarda la foto y devuelve la ruta relativa que se guarda en users.foto
    public function guardar(?UploadedFile $archivo): ?string
    {
        if (! $archivo) {
            return null;
        }

        return $archivo->store(self::CARPETA, self::DISCO);
    }

    // Borra la foto anterior del disco
    public function eliminar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }
}