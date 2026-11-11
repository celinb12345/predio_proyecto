<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\DeudasCuotas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeudorController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->esSecretariaGeneral(), 403);

        $deudas = DeudasCuotas::pendientes();

        return Inertia::render('panel/pagos/Deudores', [
            'usuario' => [
                'name' => $user->name,
                'rol' => $user->rol?->descripcion,
            ],
            'deudas' => $deudas->values()->all(),
            'resumen' => [
                'personas' => $deudas->pluck('persona_id')->unique()->count(),
                'cuotas' => $deudas->count(),
                'monto' => round((float) $deudas->sum('monto'), 2),
                'vencidas' => $deudas->where('vencida', true)->count(),
            ],
        ]);
    }
}