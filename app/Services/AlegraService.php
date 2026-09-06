<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Factura;
use Illuminate\Support\Facades\Http;

class AlegraService
{
    public function estaConfigurado(): bool
    {
        return filled(config('alegra.email')) && filled(config('alegra.token'));
    }

    public function causarFactura(Factura $factura): array
    {
        if (! $this->estaConfigurado()) {
            return [
                'ok' => false,
                'message' => 'Alegra no está configurado. Define ALEGRA_EMAIL y ALEGRA_TOKEN en el servidor.',
            ];
        }

        $factura->loadMissing(['cliente', 'servicio.planServicio']);
        $cliente = $factura->cliente;

        if (! $cliente?->factura_electronica) {
            return [
                'ok' => false,
                'message' => 'Este cliente no está marcado para factura electrónica.',
            ];
        }

        try {
            if (! $factura->alegra_id) {
                $contactId = $this->asegurarContacto($cliente);
                $payload = [
                    'date' => optional($factura->fecha_emision)->format('Y-m-d') ?: now()->toDateString(),
                    'dueDate' => optional($factura->fecha_vencimiento)->format('Y-m-d') ?: now()->toDateString(),
                    'client' => ['id' => $contactId],
                    'items' => [[
                        'id' => config('alegra.item_id') ?: null,
                        'name' => $factura->concepto ?: 'Servicio de Internet',
                        'description' => $factura->concepto ?: 'Servicio de Internet - ' . $factura->periodo,
                        'price' => (float) $factura->total,
                        'quantity' => 1,
                        'unit' => 'service',
                        'tax' => [],
                    ]],
                    'paymentForm' => 'CREDIT',
                    'paymentMethod' => 'CREDIT',
                    'useElectronicInvoice' => true,
                    'type' => 'NATIONAL',
                    'operationType' => 'STANDARD',
                ];

                if (! $payload['items'][0]['id']) {
                    unset($payload['items'][0]['id']);
                }
                if (config('alegra.number_template_id')) {
                    $payload['numberTemplate'] = ['id' => (string) config('alegra.number_template_id')];
                }

                $res = $this->request('post', '/invoices', $payload);
                $alegraId = (string) ($res['id'] ?? '');
                $factura->update(['alegra_id' => $alegraId]);
                $this->guardarDatosDian($factura, $res);
            }

            $this->enviarADian((string) $factura->alegra_id);
            $this->esperarYGuardarDian($factura);

            $factura->refresh();
            if ($factura->cufe) {
                return [
                    'ok' => true,
                    'message' => 'Factura electrónica autorizada. CUFE y QR listos en la aplicación.',
                    'alegra_id' => $factura->alegra_id,
                    'cufe' => $factura->cufe,
                ];
            }

            return [
                'ok' => true,
                'message' => 'Factura causada en Alegra. La DIAN aún no devolvió el CUFE; pulsa otra vez en unos segundos para traer el QR.',
                'alegra_id' => $factura->alegra_id,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Alegra: ' . $e->getMessage(),
            ];
        }
    }

    public function asegurarContacto(Cliente $cliente): string
    {
        if ($cliente->alegra_contact_id) {
            return (string) $cliente->alegra_contact_id;
        }

        $res = $this->request('post', '/contacts', [
            'name' => $cliente->nombre,
            'identification' => $cliente->documento,
            'phonePrimary' => $cliente->celular ?: $cliente->telefono,
            'email' => $cliente->email,
            'address' => ['address' => $cliente->direccion],
            'type' => ['client'],
            'status' => 'active',
        ]);

        $id = (string) ($res['id'] ?? '');
        $cliente->update(['alegra_contact_id' => $id]);

        return $id;
    }

    private function enviarADian(string $alegraId): void
    {
        try {
            $actual = $this->request('get', '/invoices/' . $alegraId);
            if (($actual['status'] ?? '') !== 'open') {
                $this->request('put', '/invoices/' . $alegraId . '/open', [
                    'paymentForm' => 'CREDIT',
                    'paymentMethod' => 'CREDIT',
                    'term' => 'Crédito',
                ]);
            }
        } catch (\Throwable $e) {
            // Puede ya estar abierta.
        }

        try {
            $this->request('post', '/invoices/' . $alegraId . '/stamp', [
                'generateStamp' => true,
                'generateQrCode' => true,
            ]);

            return;
        } catch (\Throwable $e) {
            // Fallback al endpoint masivo.
        }

        try {
            $this->request('post', '/invoices/stamp', [
                'ids' => [(int) $alegraId],
                'paymentForm' => 'CREDIT',
                'paymentMethod' => 'CREDIT',
                'term' => 'Crédito',
            ]);
        } catch (\Throwable $e) {
            // Queda causada; se puede reintentar para el CUFE.
        }
    }

    private function esperarYGuardarDian(Factura $factura): void
    {
        $alegraId = (string) $factura->alegra_id;
        if ($alegraId === '') {
            return;
        }

        for ($i = 0; $i < 4; $i++) {
            if ($i > 0) {
                sleep(2);
            }

            try {
                $data = $this->request('get', '/invoices/' . $alegraId);
            } catch (\Throwable $e) {
                continue;
            }

            $this->guardarDatosDian($factura, $data);
            $factura->refresh();

            if ($factura->cufe && $factura->qr_code) {
                return;
            }
        }
    }

    private function guardarDatosDian(Factura $factura, array $data): void
    {
        $stamp = is_array($data['stamp'] ?? null) ? $data['stamp'] : [];
        $numero = $data['numberTemplate']['fullNumber']
            ?? ((string) ($data['numberTemplate']['prefix'] ?? '') . (string) ($data['numberTemplate']['formattedNumber'] ?? ''));

        $updates = array_filter([
            'cufe' => $stamp['cufe'] ?? $data['cufe'] ?? $data['uuid'] ?? $factura->cufe,
            'qr_code' => $stamp['barCodeContent'] ?? $stamp['qrCode'] ?? $data['qrCode'] ?? $data['barCodeContent'] ?? $factura->qr_code,
            'estado_dian' => $stamp['legalStatus'] ?? $stamp['status'] ?? $data['legalStatus'] ?? $factura->estado_dian,
            'alegra_numero' => $numero ?: $factura->alegra_numero,
            'alegra_pdf_url' => $stamp['pdfUrl'] ?? $data['pdfUrl'] ?? $factura->alegra_pdf_url,
        ], fn ($value) => filled($value));

        if ($updates !== []) {
            $factura->update($updates);
        }
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $auth = base64_encode(config('alegra.email') . ':' . config('alegra.token'));
        $http = Http::withHeaders([
            'Authorization' => 'Basic ' . $auth,
            'Accept' => 'application/json',
        ])->timeout(40);

        $url = 'https://api.alegra.com/api/v1' . $path;
        $res = match ($method) {
            'post' => $http->post($url, $payload),
            'put' => $http->put($url, $payload),
            default => $http->get($url, $payload),
        };

        if (! $res->successful()) {
            $detail = $res->json('message') ?? $res->json('error') ?? $res->body();
            throw new \RuntimeException(is_array($detail) ? json_encode($detail) : (string) $detail);
        }

        return $res->json() ?? [];
    }
}
