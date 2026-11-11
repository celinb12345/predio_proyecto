<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProximamenteController extends Controller
{
    private const MODULOS = [
        'financiera' => 'Gestión financiera',
        'entrega' => 'Entrega-recepción',
        'mantenimiento' => 'Mantenimiento de predios',
        'pagos' => 'Gestión de pagos',
        'reportes' => 'Reportes y estadísticas',
    ];

    public function __invoke(Request $request, string $modulo): Response
    {
        abort_unless(array_key_exists($modulo, self::MODULOS), 404);

        return Inertia::render('panel/Proximamente', [
            'usuario' => [
                'name' => $request->user()->name,
                'rol' => $request->user()->rol?->descripcion,
            ],
            'titulo' => self::MODULOS[$modulo],
        ]);
    }
}