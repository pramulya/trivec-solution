<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use App\Models\User;

class GmailService
{
    protected Google_Client $client;
    protected Google_Service_Gmail $service;
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // SET TOKEN DENGAN FORMAT BENAR
        $this->client->setAccessToken([
            'access_token'  => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
        ]);

        // AUTO REFRESH
        if ($this->client->isAccessTokenExpired()) {

            if (!$user->google_refresh_token) {
                throw new \Exception('Google refresh token missing. Reconnect Gmail.');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                $user->google_refresh_token
            );

        if (isset($newToken['error'])) {
            throw new \Exception(
                'Google token refresh failed. Please reconnect Gmail.'
            );
        }


            $this->client->setAccessToken($newToken);

            $user->update([
                'google_token' => $newToken['access_token'],
            ]);
        }

        $this->service = new Google_Service_Gmail($this->client);
    }

    /* =====================
        FETCH INBOX
    ===================== */
    public function fetchInbox(int $limit = 1000): array
    {
        $messages = [];
        $pageToken = null;

        do {
            $params = [
                'maxResults' => 500, // batas aman Gmail
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->service->users_messages->listUsersMessages('me', $params);

            if ($response->getMessages()) {
                $messages = array_merge($messages, $response->getMessages());
            }

            $pageToken = $response->getNextPageToken();

            // stop kalau sudah cukup banyak
            if (count($messages) >= $limit) {
                break;
            }

        } while ($pageToken);

        return array_slice($messages, 0, $limit);
    }



    /* =====================
        FULL MESSAGE
    ===================== */
    public function getFullMessage(string $messageId): array
    {
        $message = $this->service->users_messages->get(
            'me',
            $messageId,
            ['format' => 'full']
        );

        $payload = $message->getPayload();
        $headers = collect($payload->getHeaders());

        return [
            'subject' => optional($headers->firstWhere('name', 'Subject'))->getValue(),
            'from'    => optional($headers->firstWhere('name', 'From'))->getValue(),
            'date'    => optional($headers->firstWhere('name', 'Date'))->getValue(),
            'snippet' => $message->getSnippet(),
            'body'    => $this->extractBody($payload),
        ];
    }

    /* =====================
        BODY PARSER (FULL)
    ===================== */
    private function extractBody($payload): string
    {
        // 1️⃣ Direct body
        if ($payload->getBody() && $payload->getBody()->getData()) {
            return $this->decode($payload->getBody()->getData());
        }

        // 2️⃣ Multipart (recursive)
        foreach ($payload->getParts() ?? [] as $part) {
            $result = $this->extractBody($part);
            if (!empty($result)) {
                return $result;
            }
        }

        return '';
    }

    private function decode(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
