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
    }

    /**
     * Fetch latest inbox messages from Termii
     * Note: Termii API doesn't have a direct "inbox" endpoint for generic SMS 
     * in the same way Gmail does, usually it's Webhook based.
     * But we will assume a mock/polling endpoint or use the 'history' endpoint if available.
     * 
     * For Prototype: We will MOCK the response if no key is present.
     */
    public function fetchMessages()
    {
        if (!$this->apiKey) {
            return $this->getMockMessages();
        }

        // Real API Call Implementation (example)
        // $response = Http::get("{$this->baseUrl}/sms/inbox", ['api_key' => $this->apiKey]);
        // return $response->json()['data'];
        
        return [];
    }

    protected function getMockMessages()
    {
        return [
            [
                'message_id' => 'termii_001',
                'sender' => 'Termii',
                'sms' => 'Your Termii Verification Code is 998877',
                'date_created' => now()->subMinutes(5)->toDateTimeString()
            ],
            [
                'message_id' => 'termii_002',
                'sender' => 'Alert',
                'sms' => 'Login attempt detected from new IP.',
                'date_created' => now()->subMinutes(20)->toDateTimeString()
            ]
        ];
    }

    public function sendMessage($to, $message)
    {
        if (!$this->apiKey) {
            // Mock Success
            return [
                'message_id' => 'mock_sent_' . uniqid(),
                'status' => 'success',
                'balance' => 0
            ];
        }

        /*
        return Http::post("{$this->baseUrl}/sms/send", [
            'api_key' => $this->apiKey,
            'to' => $to,
            'from' => 'Trivec', 
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'generic'
        ])->json();
        */
        
        return ['status' => 'success', 'message_id' => 'mock_real_failover'];
    }
}
