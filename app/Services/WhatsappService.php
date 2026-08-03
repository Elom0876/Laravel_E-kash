<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected ?string $apiUrl;
    protected ?string $apiKey;
    protected string $session;

    public function __construct()
    {
        $this->apiUrl = config('services.waha.url');
        $this->apiKey = config('services.waha.key');
        $this->session = config('services.waha.session');
    }

    public function envoyer(string $numero, string $message): bool
    {
        if (!$this->apiUrl || !$this->apiKey) {
            Log::warning('WAHA non configuré — message non envoyé.', [
                'numero' => $numero,
                'message' => $message,
            ]);
            return false;
        }

        $chatId = $this->formaterNumero($numero);

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/api/sendText', [
                'session' => $this->session,
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if (!$response->successful()) {
                Log::error('Échec envoi WAHA', [
                    'numero' => $numero,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Erreur envoi WAHA', ['erreur' => $e->getMessage()]);
            return false;
        }
    }

    private function formaterNumero(string $numero): string
    {
        // Retire le "+" et tout caractère non numérique
        $numeroPropre = preg_replace('/[^0-9]/', '', $numero);

        return $numeroPropre . '@c.us';
    }
}
