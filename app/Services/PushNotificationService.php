<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $expoUrl = 'https://exp.host/--/api/v2/push/send';

    public function sendToAdmins(string $title, string $body, array $data = [])
    {
        $tokens = PushToken::getAdminTokens();
        
        if (empty($tokens)) {
            Log::info('No hay tokens de admin para enviar notificación');
            return false;
        }

        return $this->send($tokens, $title, $body, $data);
    }

    public function send(array $tokens, string $title, string $body, array $data = [])
    {
        $messages = [];

        foreach ($tokens as $token) {
            if (!$this->isValidExpoToken($token)) {
                continue;
            }

            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'channelId' => 'tickets',
            ];
        }

        if (empty($messages)) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->expoUrl, $messages);

            if ($response->successful()) {
                Log::info('Notificación push enviada', ['response' => $response->json()]);
                return true;
            }

            Log::error('Error enviando notificación push', ['response' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Excepción enviando notificación push', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function isValidExpoToken(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[') || str_starts_with($token, 'ExpoPushToken[');
    }
}
