<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\TarifaActualizarRequest;
use App\Http\Requests\Panel\TarifaRegistrarRequest;
use App\Models\Predio;
use App\Models\Tarifa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TarifaController extends Controller
{
    public function store(TarifaRegistrarRequest $request, Predio $predio): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        $datos = $request->validated();

        $reemplazadas = DB::transaction(function () use ($datos, $predio) {
            // La tarifa vigente anterior del mismo tipo pasa al historial
            $anteriores = Tarifa::where('predio_id', $predio->id)
                ->where('tipo_persona_id', $datos['tipo_persona_id'])
                ->where('vigente', true)
                ->update(['vigente' => false]);

            $tarifa = Tarifa::create([
                'predio_id' => $predio->id,
                'tipo_persona_id' => $datos['tipo_persona_id'],
                'monto' => $datos['monto'],
                'resolucion' => $datos['resolucion'],
                'vigente' => true,
            ]);

            // Se asigna aparte, así no depende de que el modelo tenga el campo en $fillable
            $tarifa->vigente_hasta = $datos['vigente_hasta'] ?? null;
            $tarifa->save();

            return $anteriores;
        });

        $respuesta = redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'La tarifa se registró como vigente.');

        if ($reemplazadas > 0) {
            $respuesta->with(
                'info',
                'La tarifa anterior pasó al historial. Las reservas ya creadas conservan el monto con el que se aprobaron.'
            );
        }

        return $respuesta;
    }

    public function update(TarifaActualizarRequest $request, Predio $predio, Tarifa $tarifa): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
        abort_unless((int) $tarifa->predio_id === (int) $predio->id, 404);

        $datos = $request->validated();

        $tarifa->monto = $datos['monto'];
        $tarifa->resolucion = $datos['resolucion'];
        $tarifa->vigente_hasta = $datos['vigente_hasta'] ?? null;
        $tarifa->save();

        return redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'La tarifa se actualizó correctamente.');
    }
}