<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ResponsableRequest;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResponsableController extends Controller
{
    public function index(Request $request): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/responsables/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'responsables' => Responsable::orderBy('apellido')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'apellido', 'cargo', 'celular', 'activo']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/responsables/Create', [
            'usuario' => $this->datosUsuario($request->user()),
        ]);
    }

    public function store(ResponsableRequest $request): RedirectResponse
    {
        $this->soloSecretaria($request);

        $datos = $request->validated();
        $datos['activo'] = true;

        Responsable::create($datos);

        return redirect('/panel/responsables');
    }

    public function edit(Request $request, Responsable $responsable): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/responsables/Edit', [
            'usuario' => $this->datosUsuario($request->user()),
            'editando' => [
                'id' => $responsable->id,
                'nombre' => $responsable->nombre,
                'apellido' => $responsable->apellido,
                'cargo' => $responsable->cargo,
                'celular' => $responsable->celular,
                'activo' => $responsable->activo,
            ],
        ]);
    }

    public function update(ResponsableRequest $request, Responsable $responsable): RedirectResponse
    {
        $this->soloSecretaria($request);

        $datos = $request->validated();
        $datos['activo'] = $request->boolean('activo', $responsable->activo);

        $responsable->update($datos);

        return redirect('/panel/responsables');
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