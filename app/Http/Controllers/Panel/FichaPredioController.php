<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\EquipamientoPredio;
use App\Models\GaleriaPredio;
use App\Models\Predio;
use App\Models\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FichaPredioController extends Controller
{
    public function show(Request $request, Predio $predio): Response
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        $this->marcarTarifasVencidas($predio);

        $datos = DB::table('predios as p')
            ->join('tipo_predios as tp', 'tp.id', '=', 'p.tipo_predio_id')
            ->join('responsables as r', 'r.id', '=', 'p.responsable_id')
            ->where('p.id', $predio->id)
            ->first([
                'p.id',
                'p.nombre',
                'p.capacidad',
                'p.ubicacion',
                'p.link_google_maps',
                'p.estado',
                'tp.descripcion as tipo',
                'r.nombre as responsable_nombre',
                'r.apellido as responsable_apellido',
            ]);

        return Inertia::render('panel/predios/Show', [
            'usuario' => [
                'name' => $request->user()->name,
                'rol' => $request->user()->rol?->descripcion,
            ],
            'predio' => [
                'id' => $datos->id,
                'nombre' => $datos->nombre,
                'tipo' => $datos->tipo,
                'responsable' => trim($datos->responsable_nombre.' '.$datos->responsable_apellido),
                'capacidad' => $datos->capacidad,
                'ubicacion' => $datos->ubicacion,
                'link_google_maps' => $datos->link_google_maps,
                'estado' => $datos->estado,
            ],
            'galeria' => GaleriaPredio::where('predio_id', $predio->id)
                ->orderBy('id')
                ->get()
                ->map(fn (GaleriaPredio $g) => [
                    'id' => $g->id,
                    'tipo' => $g->tipo,
                    'descripcion' => $g->descripcion,
                    'url' => asset('storage/'.$g->archivo),
                ]),
            'inventario' => EquipamientoPredio::query()
                ->join('equipos as e', 'e.id', '=', 'equipamiento_predios.equipo_id')
                ->where('equipamiento_predios.predio_id', $predio->id)
                ->orderBy('e.descripcion')
                ->get([
                    'equipamiento_predios.id',
                    'e.descripcion as equipo',
                    'equipamiento_predios.cantidad',
                    'equipamiento_predios.estado',
                ]),
            // Lista de sugerencias para el campo de equipo
            'equipos' => DB::table('equipos')->orderBy('descripcion')->get(['id', 'descripcion']),
            'tarifario' => Tarifa::query()
                ->join('tipo_personas as tp', 'tp.id', '=', 'tarifas.tipo_persona_id')
                ->where('tarifas.predio_id', $predio->id)
                ->orderByDesc('tarifas.vigente')
                ->orderByDesc('tarifas.created_at')
                ->get([
                    'tarifas.id',
                    'tarifas.monto',
                    'tarifas.resolucion',
                    'tarifas.vigente',
                    'tarifas.vigente_hasta',
                    'tarifas.created_at',
                    'tp.descripcion as tipo_persona',
                ])
                ->map(fn (Tarifa $t) => [
                    'id' => $t->id,
                    'tipo_persona' => $t->tipo_persona,
                    'monto' => $t->monto,
                    'resolucion' => $t->resolucion,
                    'vigente' => $t->vigente,
                    'fecha' => $t->created_at?->format('Y-m-d'),
                    'vigente_hasta' => $t->vigente_hasta ? substr((string) $t->vigente_hasta, 0, 10) : null,
                ]),
            'tiposPersona' => DB::table('tipo_personas')->orderBy('descripcion')->get(['id', 'descripcion']),
        ]);
    }

    // Las tarifas de este predio cuya fecha de vencimiento ya pasó dejan de estar vigentes
    private function marcarTarifasVencidas(Predio $predio): void
    {
        DB::table('tarifas')
            ->where('predio_id', $predio->id)
            ->where('vigente', true)
            ->whereNotNull('vigente_hasta')
            ->whereDate('vigente_hasta', '<', now()->toDateString())
            ->update(['vigente' => false]);
    }
}