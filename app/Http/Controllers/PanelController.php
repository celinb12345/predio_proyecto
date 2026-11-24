<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    public function index(Request $request): Response
    {
        $hoy = now()->toDateString();
        $dentroDe7Dias = now()->addDays(7)->toDateString();

        $indicadores = DB::table('predios')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(*) filter (where estado = 'disponible') as disponibles")
            ->selectRaw("count(*) filter (where estado = 'en_mantenimiento') as en_mantenimiento")
            ->selectRaw("count(*) filter (where estado = 'inactivo') as inactivos")
            ->first();

        $mantenimientos = DB::table('mantenimientos as m')
            ->join('predios as p', 'p.id', '=', 'm.predio_id')
            ->where('m.fecha', '<=', $dentroDe7Dias)
            ->whereRaw('COALESCE(m.fecha_fin, m.fecha) >= ?', [$hoy])
            ->orderBy('m.fecha')
            ->limit(5)
            ->get(['m.id', 'm.fecha', 'm.fecha_fin', 'p.nombre as predio'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'predio' => $m->predio,
                'inicio' => Carbon::parse($m->fecha)->format('d/m/Y'),
                'fin' => Carbon::parse($m->fecha_fin ?? $m->fecha)->format('d/m/Y'),
            ]);

        return Inertia::render('panel/Index', [
            'usuario' => [
                'name' => $request->user()->name,
                'rol' => $request->user()->rol?->descripcion,
            ],
            'indicadores' => [
                'total' => (int) $indicadores->total,
                'disponibles' => (int) $indicadores->disponibles,
                'en_mantenimiento' => (int) $indicadores->en_mantenimiento,
                'inactivos' => (int) $indicadores->inactivos,
            ],
            'mantenimientos' => $mantenimientos,
        ]);
    }
}