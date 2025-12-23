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

        // Set token
        $this->client->setAccessToken([
            'access_token' => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
        ]);

        // Auto refresh token
        if ($this->client->isAccessTokenExpired()) {
            if ($user->google_refresh_token) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                    $user->google_refresh_token
                );

                $this->client->setAccessToken($newToken);

                $user->update([
                    'google_token' => $newToken['access_token'],
                ]);
            }
        }

        // ⬅️ INI YANG KEMARIN HILANG
        $this->service = new Google_Service_Gmail($this->client);
    }

    /* =====================
        FETCH INBOX
    ===================== */
    public function fetchInbox(int $limit = 5)
    {
        $response = $this->service->users_messages->listUsersMessages('me', [
            'maxResults' => $limit,
        ]);

        return $response->getMessages() ?? [];
    }

    /* =====================
        GET CONTENT
    ===================== */
    public function getMessageContent(string $messageId): string
    {
        $message = $this->service->users_messages->get(
            'me',
            $messageId,
            ['format' => 'full']
        );

        return $this->extractBody($message->getPayload());
    }


    /* =====================
        GET META (subject, from, date)
    ===================== */
    public function getMessageMeta(string $messageId): array
    {
        $message = $this->service->users_messages->get(
            'me',
            $messageId,
            [
                'format' => 'metadata',
                'metadataHeaders' => ['From', 'Subject', 'Date']
            ]
        );

        $headers = collect($message->getPayload()->getHeaders());

        return [
            'from' => optional($headers->firstWhere('name', 'From'))->getValue(),
            'subject' => optional($headers->firstWhere('name', 'Subject'))->getValue(),
            'date' => optional($headers->firstWhere('name', 'Date'))->getValue(),
        ];
    }

    public function getFullMessage(string $messageId): array
    {
        $message = $this->service->users_messages->get(
            'me',
            $messageId,
            ['format' => 'full']
        );

        $payload = $message->getPayload();
        $headers = collect($payload->getHeaders());

        $subject = optional($headers->firstWhere('name', 'Subject'))->value;
        $from    = optional($headers->firstWhere('name', 'From'))->value;
        $date    = optional($headers->firstWhere('name', 'Date'))->value;

        $body = $this->extractBody($payload);

        return [
            'subject' => $subject,
            'from'    => $from,
            'date'    => $date,
            'body'    => $body,
            'snippet' => $message->getSnippet(),
        ];
    }

    private function extractBody($payload): string
    {
        // Case 1: langsung ada body
        if ($payload->getBody() && $payload->getBody()->getData()) {
            return base64_decode(strtr(
                $payload->getBody()->getData(),
                '-_',
                '+/'
            ));
        }

        // Case 2: multipart (PALING SERING)
        foreach ($payload->getParts() ?? [] as $part) {
            $mimeType = $part->getMimeType();

            // Prioritaskan HTML
            if ($mimeType === 'text/html' && $part->getBody()->getData()) {
                return base64_decode(strtr(
                    $part->getBody()->getData(),
                    '-_',
                    '+/'
                ));
            }

            // Fallback ke text/plain
            if ($mimeType === 'text/plain' && $part->getBody()->getData()) {
                return base64_decode(strtr(
                    $part->getBody()->getData(),
                    '-_',
                    '+/'
                ));
            }

            // Recursive (nested parts)
            if ($part->getParts()) {
                $nested = $this->extractBody($part);
                if ($nested) return $nested;
            }
        }

        return '';
    }


        private function decodeBody(?string $data): ?string
    {
        if (!$data) return null;

        $data = str_replace(['-', '_'], ['+', '/'], $data);
        return base64_decode($data);
    }

}
