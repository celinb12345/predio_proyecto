<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\EquipamientoPredioRequest;
use App\Models\EquipamientoPredio;
use App\Models\Predio;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipamientoPredioController extends Controller
{
    public function store(EquipamientoPredioRequest $request, Predio $predio): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        $datos = $request->validated();

        try {
            DB::transaction(function () use ($datos, $predio) {
                $equipo = new EquipamientoPredio;
                $equipo->predio_id = $predio->id;
                $equipo->equipo_id = $this->equipoPorNombre($datos['equipo']);
                $equipo->cantidad = $datos['cantidad'];
                $equipo->estado = $datos['estado'];
                $equipo->save();
            });
        } catch (QueryException) {
            // Llega aquí si el equipo ya está en este predio (restricción única)
            return back()
                ->withErrors(['equipo' => 'Este equipo ya está registrado en el predio.'])
                ->with('error', 'No se pudo agregar el equipo porque ya está en el inventario.');
        }

        return redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'El equipo se agregó al inventario.');
    }

    public function update(EquipamientoPredioRequest $request, Predio $predio, EquipamientoPredio $equipamiento): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
        abort_unless((int) $equipamiento->predio_id === (int) $predio->id, 404);

        $datos = $request->validated();

        $equipamiento->cantidad = $datos['cantidad'];
        $equipamiento->estado = $datos['estado'];
        $equipamiento->save();

        return redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'El inventario se actualizó correctamente.');
    }

    public function destroy(Request $request, Predio $predio, EquipamientoPredio $equipamiento): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
        abort_unless((int) $equipamiento->predio_id === (int) $predio->id, 404);

        try {
            $equipamiento->delete();
        } catch (QueryException) {
            return back()
                ->with('error', 'El equipo no se pudo quitar porque ya tiene actas de entrega.');
        }

        return redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'El equipo se quitó del predio.');
    }

    // Busca el equipo sin importar mayúsculas ni espacios; si no existe, lo crea
    private function equipoPorNombre(string $nombre): int
    {
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre) ?? $nombre);

        $existente = DB::table('equipos')
            ->whereRaw('lower(descripcion) = ?', [mb_strtolower($nombre)])
            ->value('id');

        if ($existente !== null) {
            return (int) $existente;
        }

        return (int) DB::table('equipos')->insertGetId(['descripcion' => $nombre]);
    }
}