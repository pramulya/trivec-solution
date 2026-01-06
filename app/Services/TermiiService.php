<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TermiiService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.ng.termii.com/api';

    public function __construct()
    {
        $this->apiKey = config('services.termii.key');
        $this->baseUrl = config('services.termii.url', 'https://v3.api.termii.com/api');
    }

    /**
     * Fetch latest inbox messages from Termii
     */
    public function fetchMessages()
    {
        // For now, Termii doesn't have a simple "pull" inbox API that fits this model perfectly.
        // We rely on Webhooks. This method might remain mocked or unused for now.
        return [];
    }

    protected function getMockMessages()
    {
        // Mock data removed for brevity/production-readiness
        return [];
    }

    public function sendMessage($to, $message)
    {
        if (!$this->apiKey) {
            // Mock Success (Fallback for dev without keys)
            return [
                'message_id' => 'mock_sent_' . uniqid(),
                'status' => 'success',
                'balance' => 0
            ];
        }

        $response = Http::post("{$this->baseUrl}/sms/send", [
            'api_key' => $this->apiKey,
            'to' => $to,
            'from' => 'N-Alert', // Default sender ID, usually needs to be "N-Alert" or a registered ID.
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'generic'
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("Termii Error: " . $response->body());
    }
}
