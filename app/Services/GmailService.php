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
    /* =====================
        FETCH MESSAGES (BY LABEL)
    ===================== */
    public function fetchMessages(string $labelId = 'INBOX', int $limit = 50): array
    {
        $messages = [];
        $pageToken = null;

        // Use smaller batch size for better performance
        $batchSize = min($limit, 50);

        do {
            $params = [
                'maxResults' => $batchSize,
                'labelIds'   => [$labelId],
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
        $labelIds = $message->getLabelIds() ?? [];

        $bodyData = $this->extractBody($payload);

        return [
            'subject'  => optional($headers->firstWhere('name', 'Subject'))->getValue(),
            'from'     => optional($headers->firstWhere('name', 'From'))->getValue(),
            'date'     => optional($headers->firstWhere('name', 'Date'))->getValue(),
            'snippet'  => $message->getSnippet(),
            'labelIds' => $labelIds,
            'body'     => $bodyData['body'],
            'is_html'  => $bodyData['is_html'],
        ];
    }

    /* =====================
        BODY PARSER (FULL) - Prioritizes HTML over plain text
    ===================== */
    private function extractBody($payload): array
    {
        $htmlBody = '';
        $textBody = '';

        // 1️⃣ Direct body (single part message)
        if ($payload->getBody() && $payload->getBody()->getData()) {
            $mimeType = $payload->getMimeType();
            $body = $this->decode($payload->getBody()->getData());
            
            if ($mimeType === 'text/html') {
                $htmlBody = $body;
            } else {
                $textBody = $body;
            }
        }

        // 2️⃣ Multipart (recursive)
        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                $mimeType = $part->getMimeType();
                
                if ($mimeType === 'text/html') {
                    if ($part->getBody() && $part->getBody()->getData()) {
                        $htmlBody = $this->decode($part->getBody()->getData());
                    }
                } elseif ($mimeType === 'text/plain') {
                    if ($part->getBody() && $part->getBody()->getData()) {
                        $textBody = $this->decode($part->getBody()->getData());
                    }
                } elseif (str_starts_with($mimeType, 'multipart/')) {
                    // Recursively check nested multipart
                    $nested = $this->extractBody($part);
                    if (!empty($nested['body'])) {
                        if ($nested['is_html']) {
                            $htmlBody = $nested['body'];
                        } else {
                            $textBody = $nested['body'];
                        }
                    }
                }
            }
        }

        // Prioritize HTML, fallback to plain text
        if (!empty($htmlBody)) {
            return ['body' => $htmlBody, 'is_html' => true];
        } elseif (!empty($textBody)) {
            return ['body' => $textBody, 'is_html' => false];
        }

        return ['body' => '', 'is_html' => false];
    }

    private function decode(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /* =====================
        TOGGLE STAR
    ===================== */
    public function toggleStar(string $gmailMessageId, bool $star): void
    {
        $mods = new \Google_Service_Gmail_ModifyMessageRequest();
        
        if ($star) {
            $mods->setAddLabelIds(['STARRED']);
            $mods->setRemoveLabelIds([]);
        } else {
            $mods->setAddLabelIds([]);
            $mods->setRemoveLabelIds(['STARRED']);
        }

        $this->service->users_messages->modify('me', $gmailMessageId, $mods);
    }

    /* =====================
        SEND EMAIL
    ===================== */
    public function sendEmail(string $to, string $subject, string $body)
    {
        $strSubject = 'Subject: ' . $subject . "\r\n";
        $strTo = 'To: ' . $to . "\r\n";
        $strContentType = 'Content-Type: text/html; charset=utf-8' . "\r\n";
        $strMimeVersion = 'MIME-Version: 1.0' . "\r\n";
        
        // Combine headers and body
        $strRawMessage = $strSubject . $strTo . $strContentType . $strMimeVersion . "\r\n" . $body;

        // Base64URL Encode (Required by Gmail API)
        $base64Message = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');

        $msg = new \Google_Service_Gmail_Message();
        $msg->setRaw($base64Message);

        return $this->service->users_messages->send('me', $msg);
    }

    /* =====================
        TRASH / UNTRASH
    ===================== */
    public function trashMessage(string $id)
    {
        return $this->service->users_messages->trash('me', $id);
    }

    public function untrashMessage(string $id)
    {
        return $this->service->users_messages->untrash('me', $id);
    }

    /* =====================
        MODIFY LABELS (For Spam)
    ===================== */
    public function modifyLabels(string $id, array $add = [], array $remove = [])
    {
        $mods = new \Google_Service_Gmail_ModifyMessageRequest();
        $mods->setAddLabelIds($add);
        $mods->setRemoveLabelIds($remove);

        return $this->service->users_messages->modify('me', $id, $mods);
    }
}
