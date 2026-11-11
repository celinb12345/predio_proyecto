<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\PredioRequest;
use App\Models\Predio;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PredioController extends Controller
{
    public function index(Request $request): Response
    {
        $this->soloSecretaria($request);

        $this->marcarTarifasVencidas();
         \App\Support\EstadoMantenimiento::sincronizar();

        $buscar = trim((string) $request->query('buscar', ''));

        $predios = Predio::query()
            ->join('tipo_predios as tp', 'tp.id', '=', 'predios.tipo_predio_id')
            ->join('responsables as r', 'r.id', '=', 'predios.responsable_id')
            ->when($buscar !== '', fn ($q) => $q->where('predios.nombre', 'ilike', "%{$buscar}%"))
            ->orderBy('predios.nombre')
            ->select(
                'predios.id',
                'predios.nombre',
                'predios.capacidad',
                'predios.estado',
                'tp.descripcion as tipo',
                'r.nombre as responsable_nombre',
                'r.apellido as responsable_apellido',
            )
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'tipo' => $p->tipo,
                'responsable' => trim($p->responsable_nombre.' '.$p->responsable_apellido),
                'capacidad' => $p->capacidad,
                'estado' => $p->estado,
            ]);

        return Inertia::render('panel/predios/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'predios' => $predios,
            'filtros' => ['buscar' => $buscar],
            'alertasTarifas' => $this->tarifasPorVencer(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/predios/Create', [
            'usuario' => $this->datosUsuario($request->user()),
            'tipos' => DB::table('tipo_predios')->orderBy('descripcion')->get(['id', 'descripcion']),
            'responsables' => Responsable::where('activo', true)
                ->orderBy('apellido')
                ->get(['id', 'nombre', 'apellido']),
        ]);
    }

    public function store(PredioRequest $request): RedirectResponse
    {
        $this->soloSecretaria($request);

        $predio = Predio::create($request->validated());

        return redirect('/panel/predios')
            ->with('exito', "El predio «{$predio->nombre}» se registró correctamente.");
    }

    public function edit(Request $request, Predio $predio): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/predios/Edit', [
            'usuario' => $this->datosUsuario($request->user()),
            'editando' => [
                'id' => $predio->id,
                'nombre' => $predio->nombre,
                'capacidad' => $predio->capacidad,
                'ubicacion' => $predio->ubicacion,
                'link_google_maps' => $predio->link_google_maps,
                'estado' => $predio->estado,
                'tipo_predio_id' => $predio->tipo_predio_id,
                'responsable_id' => $predio->responsable_id,
            ],
            'tipos' => DB::table('tipo_predios')->orderBy('descripcion')->get(['id', 'descripcion']),
            'responsables' => Responsable::where('activo', true)
                ->orWhere('id', $predio->responsable_id)
                ->orderBy('apellido')
                ->get(['id', 'nombre', 'apellido']),
        ]);
    }

    public function update(PredioRequest $request, Predio $predio): RedirectResponse
    {
        $this->soloSecretaria($request);

        $estadoAnterior = $predio->estado;

        $predio->update($request->validated());

        $respuesta = redirect('/panel/predios')
            ->with('exito', "Los datos de «{$predio->nombre}» se guardaron correctamente.");

        // Si el predio deja de estar disponible con fechas reservadas, se avisa.
        // El sistema no cancela esas reservas de forma automática.
        if ($estadoAnterior === 'disponible' && $predio->estado !== 'disponible') {
            $proximas = $this->fechasProximas($predio);

            if ($proximas > 0) {
                $respuesta->with(
                    'advertencia',
                    "«{$predio->nombre}» tiene {$proximas} fecha(s) reservada(s) desde hoy. Las reservas no se cancelan automáticamente; revísalas en Liberación de fechas."
                );
            }
        }

        return $respuesta;
    }

    // Las tarifas cuya fecha de vencimiento ya pasó dejan de estar vigentes
    private function marcarTarifasVencidas(): void
    {
        DB::table('tarifas')
            ->where('vigente', true)
            ->whereNotNull('vigente_hasta')
            ->whereDate('vigente_hasta', '<', now()->toDateString())
            ->update(['vigente' => false]);
    }

    // Tarifas que vencen dentro de los próximos 4 días, incluido hoy
    private function tarifasPorVencer(): array
    {
        $hoy = now()->startOfDay();

        return DB::table('tarifas as t')
            ->join('predios as p', 'p.id', '=', 't.predio_id')
            ->join('tipo_personas as tp', 'tp.id', '=', 't.tipo_persona_id')
            ->where('t.vigente', true)
            ->whereNotNull('t.vigente_hasta')
            ->whereBetween('t.vigente_hasta', [$hoy->toDateString(), $hoy->copy()->addDays(4)->toDateString()])
            ->orderBy('t.vigente_hasta')
            ->get(['p.nombre as predio', 'tp.descripcion as tipo', 't.vigente_hasta'])
            ->map(function ($fila) use ($hoy) {
                $vence = Carbon::parse($fila->vigente_hasta)->startOfDay();

                return [
                    'predio' => $fila->predio,
                    'tipo' => $fila->tipo,
                    'vence' => $vence->toDateString(),
                    'dias' => (int) $hoy->diffInDays($vence),
                ];
            })
            ->values()
            ->all();
    }

    private function fechasProximas(Predio $predio): int
    {
        return DB::table('solicitud_predios as sp')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->where('sp.predio_id', $predio->id)
            ->where('sp.activo', true)
            ->where('s.estado', 'aprobada')
            ->where('sp.fecha', '>=', now()->toDateString())
            ->count();
    }

    private function soloSecretaria(Request $request): void
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}