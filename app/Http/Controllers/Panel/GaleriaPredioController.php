<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\GaleriaPredioRequest;
use App\Models\GaleriaPredio;
use App\Models\Predio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriaPredioController extends Controller
{
    public function store(GaleriaPredioRequest $request, Predio $predio): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        $datos = $request->validated();

        GaleriaPredio::create([
            'tipo' => $datos['tipo'],
            'descripcion' => $datos['descripcion'] ?? null,
            'archivo' => $request->file('archivo')->store('galeria_predios', 'public'),
            'predio_id' => $predio->id,
        ]);

        return redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'La foto se agregó a la galería.');
    }

    public function destroy(Request $request, Predio $predio, GaleriaPredio $galeria): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
        abort_unless($galeria->predio_id === $predio->id, 404);

        Storage::disk('public')->delete($galeria->archivo);
        $galeria->delete();

        return redirect("/panel/predios/{$predio->id}")
            ->with('exito', 'La foto se eliminó de la galería.');
    }
}