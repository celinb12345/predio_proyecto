<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Comunicación con la API de PagoFácil (método QR).
 * Solo se usa desde el servidor: las credenciales nunca llegan al navegador.
 */
class PagoFacilService
{
    private const CLAVE_TOKEN = 'pagofacil:access_token';

    /**
     * @param  array{
     *     cliente_nombre: string, documento_tipo: int, documento_id: string,
     *     telefono: string, email: string, id_empresa: string, codigo_cliente: string,
     *     monto: float, detalle: array<int, array{producto: string, cantidad: int, precio: float}>
     * }  $datos
     * @return array{transaction_id: string, expira_en: CarbonImmutable, qr_base64: string}
     */
    public function generarQr(array $datos): array
    {
        $cuerpo = [
            'paymentMethod' => config('services.pagofacil.metodo_id'),
            'clientName' => $datos['cliente_nombre'],
            'documentType' => $datos['documento_tipo'],
            'documentId' => $datos['documento_id'],
            'phoneNumber' => $datos['telefono'],
            'email' => $datos['email'],
            'paymentNumber' => $datos['id_empresa'],
            'amount' => round($datos['monto'], 2),
            'currency' => (int) config('services.pagofacil.moneda'),
            'clientCode' => $datos['codigo_cliente'],
            'orderDetail' => collect($datos['detalle'])
                ->values()
                ->map(fn (array $linea, int $indice) => [
                    'serial' => $indice + 1,
                    'product' => $linea['producto'],
                    'quantity' => $linea['cantidad'],
                    'price' => round($linea['precio'], 2),
                    'discount' => 0,
                    'total' => round($linea['precio'] * $linea['cantidad'], 2),
                ])
                ->all(),
        ];

        $url = config('services.pagofacil.callback_url');
        if ($url) {
            $cuerpo['callbackUrl'] = $url;
        }

        $respuesta = $this->enviar('/generate-qr', $cuerpo);
        $valores = $respuesta['values'] ?? [];

        if (empty($valores['transactionId']) || empty($valores['qrBase64'])) {
            throw new RuntimeException('PagoFácil no devolvió el código QR. Intenta nuevamente.');
        }

        return [
            'transaction_id' => (string) $valores['transactionId'],
            'expira_en' => CarbonImmutable::parse((string) $valores['expirationDate'], config('app.timezone')),
            'qr_base64' => (string) $valores['qrBase64'],
        ];
    }

    /** Estado actual de una orden, consultado por nuestro identificador de empresa. */
    public function consultarEstado(string $idEmpresa): string
    {
        $respuesta = $this->enviar('/query-transaction', [
            'companyTransactionId' => $idEmpresa,
        ]);

        return (string) ($respuesta['values']['paymentStatus'] ?? '');
    }

    /** Indica si el estado de PagoFácil equivale a "pagado" (valor configurado en .env). */
    public function estaPagado(string $estado): bool
    {
        $pagado = config('services.pagofacil.estado_pagado');

        return $pagado !== null && $pagado !== '' && $estado === (string) $pagado;
    }

    private function enviar(string $ruta, array $cuerpo, bool $reintentar = true): array
    {
        try {
            $respuesta = $this->cliente()->post($ruta, $cuerpo);
        } catch (ConnectionException) {
            Log::error('PagoFácil: sin conexión', ['ruta' => $ruta]);

            throw new RuntimeException('No se pudo conectar con PagoFácil. Intenta nuevamente.');
        }

        // Token vencido: se pide uno nuevo y se reintenta una sola vez
        if ($respuesta->status() === 401 && $reintentar) {
            Cache::forget(self::CLAVE_TOKEN);

            return $this->enviar($ruta, $cuerpo, false);
        }

        $datos = $respuesta->json() ?? [];

        if (! $respuesta->successful() || (int) ($datos['error'] ?? 1) !== 0) {
            Log::warning('PagoFácil: operación rechazada', [
                'ruta' => $ruta,
                'http' => $respuesta->status(),
                'status' => $datos['status'] ?? null,
                'mensaje' => $datos['message'] ?? null,
            ]);

            throw new RuntimeException('PagoFácil no pudo completar la operación. Intenta nuevamente.');
        }

        return $datos;
    }

    private function base(): PendingRequest
    {
        return Http::baseUrl((string) config('services.pagofacil.url'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pagofacil.timeout'))
            ->withHeaders(['Response-Language' => 'es']);
    }

    private function cliente(): PendingRequest
    {
        return $this->base()->withToken($this->token());
    }

    private function token(): string
    {
        $guardado = Cache::get(self::CLAVE_TOKEN);
        if (is_string($guardado) && $guardado !== '') {
            return Crypt::decryptString($guardado);
        }

        try {
            $respuesta = $this->base()
                ->withHeaders([
                    'tcTokenSecret' => config('services.pagofacil.secret'),
                    'tcTokenService' => config('services.pagofacil.service'),
                ])
                ->post('/login');
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con PagoFácil. Intenta nuevamente.');
        }

        $token = $respuesta->json('values.accessToken');

        if (! $respuesta->successful() || (int) ($respuesta->json('error') ?? 1) !== 0 || ! is_string($token)) {
            Log::warning('PagoFácil: autenticación rechazada', ['http' => $respuesta->status()]);

            throw new RuntimeException('No se pudo autenticar con PagoFácil. Revisa las credenciales.');
        }

        $minutos = (int) floor((float) $respuesta->json('values.expiresInMinutes', 60));

        // Se guarda cifrado y se renueva 10 minutos antes de que venza
        Cache::put(self::CLAVE_TOKEN, Crypt::encryptString($token), now()->addMinutes(max(1, $minutos - 10)));

        return $token;
    }
}