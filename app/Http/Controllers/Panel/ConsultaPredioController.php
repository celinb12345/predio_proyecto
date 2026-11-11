<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConsultaPredioController extends Controller
{
    private const EXTENSIONES_IMAGEN = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const EXTENSIONES_VIDEO = ['mp4', 'webm', 'mov'];

    public function index(Request $request): Response
    {
        $paginado = DB::table('predios as p')
            ->leftJoin('tipo_predios as tp', 'tp.id', '=', 'p.tipo_predio_id')
            ->orderBy('p.nombre')
            ->paginate(12, [
                'p.id',
                'p.nombre',
                'p.capacidad',
                'p.ubicacion',
                'p.estado',
                'tp.descripcion as tipo',
            ])
            ->withQueryString();

        $fotos = DB::table('galeria_predios')
            ->whereIn('predio_id', collect($paginado->items())->pluck('id')->all())
            ->orderBy('id')
            ->get(['predio_id', 'archivo'])
            ->groupBy('predio_id');

        $predios = $paginado->through(fn ($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'tipo' => $p->tipo,
            'capacidad' => $p->capacidad,
            'ubicacion' => $p->ubicacion,
            'estado' => $p->estado,
            'portada' => $this->portada($fotos->get($p->id, collect())),
        ]);

        return Inertia::render('panel/consulta/predios/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'predios' => $predios,
        ]);
    }

    public function show(Request $request, Predio $predio): Response
    {
        $usuario = $request->user();

        $tipoPredio = DB::table('tipo_predios')
            ->where('id', $predio->tipo_predio_id)
            ->value('descripcion');

        $galeria = DB::table('galeria_predios')
            ->where('predio_id', $predio->id)
            ->orderBy('id')
            ->get(['archivo', 'tipo', 'descripcion'])
            ->map(fn ($g) => [
                'url' => asset('storage/'.$g->archivo),
                'tipo' => $g->tipo,
                'descripcion' => $g->descripcion,
                'es_imagen' => $this->esImagen($g->archivo),
                'es_video' => $this->esVideo($g->archivo),
            ])
            ->values();

        // Tarifa vigente del predio según el tipo de persona del usuario
        $tipoPersonaId = DB::table('personas')
            ->where('id', $usuario->persona_id)
            ->value('tipo_persona_id');

        $tarifa = $tipoPersonaId
            ? DB::table('tarifas')
                ->where('predio_id', $predio->id)
                ->where('tipo_persona_id', $tipoPersonaId)
                ->where('vigente', true)
                ->first(['monto', 'resolucion'])
            : null;

        $inventario = DB::table('equipamiento_predios as ep')
            ->join('equipos as eq', 'eq.id', '=', 'ep.equipo_id')
            ->where('ep.predio_id', $predio->id)
            ->orderBy('eq.descripcion')
            ->get(['eq.descripcion as equipo', 'ep.cantidad', 'ep.estado'])
            ->map(fn ($e) => [
                'equipo' => $e->equipo,
                'cantidad' => (int) $e->cantidad,
                'estado' => $e->estado,
            ])
            ->values();

        return Inertia::render('panel/consulta/predios/Show', [
            'usuario' => $this->datosUsuario($usuario),
            'predio' => [
                'id' => $predio->id,
                'nombre' => $predio->nombre,
                'tipo' => $tipoPredio,
                'capacidad' => $predio->capacidad,
                'ubicacion' => $predio->ubicacion,
                'link_google_maps' => $predio->link_google_maps,
                'estado' => $predio->estado,
            ],
            'galeria' => $galeria,
            'tarifa' => $tarifa ? [
                'monto' => round((float) $tarifa->monto, 2),
                'resolucion' => $tarifa->resolucion,
            ] : null,
            'tienePersona' => $usuario->persona_id !== null,
            'inventario' => $inventario,
        ]);
    }

    private function portada(Collection $fotos): ?string
    {
        $imagen = $fotos->first(fn ($f) => $this->esImagen($f->archivo));

        return $imagen ? asset('storage/'.$imagen->archivo) : null;
    }

    private function esImagen(string $archivo): bool
    {
        return in_array(strtolower(pathinfo($archivo, PATHINFO_EXTENSION)), self::EXTENSIONES_IMAGEN, true);
    }

    private function esVideo(string $archivo): bool
    {
        return in_array(strtolower(pathinfo($archivo, PATHINFO_EXTENSION)), self::EXTENSIONES_VIDEO, true);
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}