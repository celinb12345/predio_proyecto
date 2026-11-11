<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $predios = DB::table('predios')
            ->selectRaw("COUNT(*) as total, COUNT(*) FILTER (WHERE estado = 'disponible') as disponibles")
            ->first();

        $reservasAnio = DB::table('reservas')
            ->whereIn('estado', ['confirmada', 'finalizada'])
            ->whereYear('created_at', now()->year)
            ->count();

        return Inertia::render('Welcome', [
            'estadisticas' => [
                'predios_total' => (int) $predios->total,
                'predios_disponibles' => (int) $predios->disponibles,
                'reservas_anio' => $reservasAnio,
                'anio' => now()->year,
            ],
            'autenticado' => $request->user() !== null,
        ]);
    }
}