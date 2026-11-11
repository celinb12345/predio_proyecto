<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreUsuarioRequest;
use App\Http\Requests\Panel\UpdateUsuarioRequest;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use App\Services\AlmacenFotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $this->soloSecretaria($request);

        $buscar = trim((string) $request->query('buscar', ''));

        $usuarios = User::query()
            ->with('rol')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('name', 'ilike', "%{$buscar}%")
                        ->orWhere('email', 'ilike', "%{$buscar}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'foto' => $u->foto ? asset('storage/'.$u->foto) : null,
                'rol' => $u->rol?->descripcion,
                'activo' => $u->activo,
                'ultimo_acceso' => $u->ultimo_acceso?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('panel/usuarios/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'usuarios' => $usuarios,
            'filtros' => ['buscar' => $buscar],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/usuarios/Create', [
            'usuario' => $this->datosUsuario($request->user()),
            'roles' => Rol::orderBy('id')->get(['id', 'descripcion']),
            'tiposPersona' => DB::table('tipo_personas')->orderBy('descripcion')->get(['id', 'descripcion']),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $this->soloSecretaria($request);
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $request) {
            $persona = Persona::create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'ci' => $datos['ci'] ?? null,
                'celular' => $datos['celular'] ?? null,
                'tipo_persona_id' => $datos['tipo_persona_id'],
            ]);

            User::create([
                'name' => trim($datos['nombre'].' '.$datos['apellido']),
                'email' => $datos['email'],
                'password' => $datos['password'],
                'foto' => app(AlmacenFotoService::class)->guardar($request->file('foto')),
                'persona_id' => $persona->id,
                'role_id' => $datos['role_id'],
                'activo' => true,
            ]);
        });

        return redirect('/panel/usuarios')
            ->with('exito', "Se registró a {$datos['nombre']} {$datos['apellido']} correctamente.");
    }

    public function edit(Request $request, User $usuario): Response
    {
        $this->soloSecretaria($request);

        return Inertia::render('panel/usuarios/Edit', [
            'usuario' => $this->datosUsuario($request->user()),
            'editando' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'role_id' => $usuario->role_id,
                'activo' => $usuario->activo,
                'foto' => $usuario->foto ? asset('storage/'.$usuario->foto) : null,
            ],
            'roles' => Rol::orderBy('id')->get(['id', 'descripcion']),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->soloSecretaria($request);
        $datos = $request->validated();

        $activo = $request->boolean('activo', $usuario->activo);

        if ($usuario->is($request->user()) && ! $activo) {
            return back()->withErrors(['activo' => 'No puedes desactivar tu propia cuenta.']);
        }

        $desactivando = (bool) $usuario->activo && ! $activo;
        $cambiaRol = (int) $usuario->role_id !== (int) $datos['role_id'];

        $cambios = [
            'role_id' => $datos['role_id'],
            'activo' => $activo,
        ];

        if (! empty($datos['password'])) {
            $cambios['password'] = $datos['password'];
        }

        if ($request->hasFile('foto')) {
            $almacen = app(AlmacenFotoService::class);
            $almacen->eliminar($usuario->foto);
            $cambios['foto'] = $almacen->guardar($request->file('foto'));
        }

        $usuario->update($cambios);

        return match (true) {
            $desactivando => redirect('/panel/usuarios')->with(
                'advertencia',
                "La cuenta de {$usuario->name} quedó inactiva y no podrá ingresar al sistema."
            ),
            $cambiaRol => redirect('/panel/usuarios')->with(
                'advertencia',
                "Se cambió el rol de {$usuario->name}. Verá otro menú la próxima vez que ingrese."
            ),
            default => redirect('/panel/usuarios')->with(
                'exito',
                'Los cambios del usuario se guardaron correctamente.'
            ),
        };
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