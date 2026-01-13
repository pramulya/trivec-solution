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
    public function fetchMessages(string $labelId = 'INBOX', int $limit = 50, ?string $pageToken = null): array
    {
        $messages = [];
        $nextPageToken = null;

        $params = [
            'maxResults' => $limit,
            'labelIds'   => [$labelId],
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $response = $this->service->users_messages->listUsersMessages('me', $params);

            if ($response->getMessages()) {
                $messages = $response->getMessages();
            }

            $nextPageToken = $response->getNextPageToken();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gmail Fetch Error: ' . $e->getMessage());
        }

        return [
            'messages' => $messages,
            'nextPageToken' => $nextPageToken
        ];
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

        // 1. Extract & Save Attachments (First to get CIDs)
        $attachments = $this->processAttachments($messageId, $payload);

        // 2. Build CID Map (Content-ID -> Local Attachment ID)
        $cidMap = [];
        foreach ($attachments as $att) {
            if ($att instanceof \App\Models\Attachment && $att->content_id) {
                 // Remove < and > from content id if present, actually standard is <id>
                 // But in HTML it might be cid:id without brackets.
                 // We store stripped? Or raw? Let's strip brackets for consistency.
                 $cidMap[$att->content_id] = $att->id;
            }
        }

        // 3. Extract Body (with CID replacement)
        $bodyData = $this->extractBody($payload, $cidMap);

        return [
            'id'       => $messageId,
            'subject'  => optional($headers->firstWhere('name', 'Subject'))->getValue(),
            'from'     => optional($headers->firstWhere('name', 'From'))->getValue(),
            'date'     => optional($headers->firstWhere('name', 'Date'))->getValue(),
            'snippet'  => $message->getSnippet(),
            'labelIds' => $labelIds,
            'body'     => $bodyData['body'],
            'is_html'  => $bodyData['is_html'],
            'attachments' => $attachments,
        ];
    }

    /* =====================
        ATTACHMENT PROCESSING
    ===================== */
    private function processAttachments(string $messageId, $payload): array
    {
        $attachments = [];
        $parts = $payload->getParts();

        if (!$parts) {
            return [];
        }

        foreach ($parts as $part) {
            $this->recursiveFindAttachments($messageId, $part, $attachments);
        }

        return $attachments;
    }

    private function recursiveFindAttachments(string $messageId, $part, array &$results)
    {
        // Check if it has data (attachmentId represents a large payload to be fetched)
        // We relax the filename check because inline images sometimes lack it.
        $body = $part->getBody();
        if ($body && $body->getAttachmentId()) {
            
            $filename = $part->getFilename();
            $mimeType = $part->getMimeType();
            $attachmentId = $body->getAttachmentId();
            $size = $body->getSize();

            // Extract Content-ID
            $headers = collect($part->getHeaders());
            $contentIdHeader = optional($headers->firstWhere('name', 'Content-ID'))->getValue();
            $contentId = $contentIdHeader ? trim($contentIdHeader, '<>') : null;

            // Check if already exists to avoid re-downloading
            $existing = \App\Models\Attachment::where('message_id', $messageId)
                        ->where('attachment_id', $attachmentId)
                        ->first();

            if ($existing) {
                // Update content_id if missing (migration support)
                if ($contentId && !$existing->content_id) {
                    $existing->update(['content_id' => $contentId]);
                }
                $results[] = $existing;
                return;
            }

            // Download Content
            try {
                $attachmentObj = $this->service->users_messages_attachments->get('me', $messageId, $attachmentId);
                $data = $this->decode($attachmentObj->getData());

                // Secure Save: storage/app/attachments/{message_id}/{sanitized_filename}
                // If filename is empty (inline image often), generate one
                if (empty($filename)) {
                    $ext = explode('/', $mimeType)[1] ?? 'dat';
                    $filename = "inline-{$attachmentId}.{$ext}";
                }

                $safeFilename = \Illuminate\Support\Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $path = "attachments/{$messageId}/{$safeFilename}";
                
                \Illuminate\Support\Facades\Storage::put($path, $data);

                // Save to DB
                $attachmentModel = \App\Models\Attachment::create([
                    'message_id'    => $messageId,
                    'attachment_id' => $attachmentId,
                    'content_id'    => $contentId,
                    'filename'      => $filename,
                    'mime_type'     => $mimeType,
                    'path'          => $path,
                    'size'          => $size,
                ]);

                $results[] = $attachmentModel;

            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::error("Failed to download attachment {$filename}: " . $e->getMessage());
            }
        }

        // Recursively check parts
        if ($part->getParts()) {
            foreach ($part->getParts() as $subPart) {
                $this->recursiveFindAttachments($messageId, $subPart, $results);
            }
        }
    }

    /* =====================
        BODY PARSER (FULL) - Prioritizes HTML over plain text
    ===================== */
    private function extractBody($payload, array $cidMap = []): array
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
                $filename = $part->getFilename();

                // Skip attachments in body extraction (handled separately)
                // UNLESS it is an inline image providing content for html parts?
                // Actually, standard attachments are skipped here, so 'extractBody' builds the text skeleton.
                // We don't want to skip text/html parts.
                if (!empty($filename) && !in_array($mimeType, ['text/html', 'text/plain'])) {
                    continue;
                }
                
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
                    $nested = $this->extractBody($part, $cidMap); // Recurse with cidMap (though not used deep down until returning string)
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

        // Replacer Logic
        $replacer = function ($content) use ($cidMap) {
            foreach ($cidMap as $cid => $localId) {
                // Replace "cid:xyz" with "/attachments/{id}"
                $content = str_replace("cid:{$cid}", "/attachments/{$localId}", $content);
            }
            return $content;
        };

        // Prioritize HTML, fallback to plain text
        if (!empty($htmlBody)) {
            return ['body' => $replacer($htmlBody), 'is_html' => true];
        } elseif (!empty($textBody)) {
            return ['body' => $textBody, 'is_html' => false]; // Plain text usually doesn't have CIDs
        }

        return ['body' => '(No body content)', 'is_html' => false];
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
        SEND EMAIL (WITH ATTACHMENTS)
    ===================== */
    public function sendEmail(string $to, string $subject, string $body, array $attachments = [])
    {
        $mime = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email());
        $mime->from($this->user->email, $this->user->name)
             ->to($to)
             ->subject($subject)
             ->html($body);

        // Attach files
        foreach ($attachments as $file) {
            // $file can be an UploadedFile object or path
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $mime->attach($file->getRealPath(), [
                    'as' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                ]);
            }
        }

        $rawMessage = $mime->toString();
        $base64Message = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

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
