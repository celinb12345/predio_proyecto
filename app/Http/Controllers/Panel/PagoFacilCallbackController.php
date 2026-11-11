<?php

namespace App\Http\Controllers;

use App\Services\PagoQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PagoFacilCallbackController extends Controller
{
    public function __invoke(Request $request, PagoQrService $qr): JsonResponse
    {
        $datos = $request->validate([
            'PedidoID' => ['required', 'string', 'max:60'],
        ]);

        try {
            $cuotaId = $qr->confirmarPorIdEmpresa($datos['PedidoID']);
        } catch (RuntimeException) {
            // Si PagoFácil no responde, se pide que reintente más tarde
            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Intenta nuevamente.',
                'values' => false,
            ], 503);
        }

        if ($cuotaId === null) {
            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Pedido no encontrado.',
                'values' => false,
            ], 404);
        }

        return response()->json([
            'error' => 0,
            'status' => 1,
            'message' => 'Notificación recibida.',
            'values' => true,
        ]);
    }
}