<?php

namespace App\Services;

class PhishingDetectionService
{
    public function analyze(array $email): array
    {
        $score = 0;
        $reasons = [];

        $subject = strtolower($email['subject'] ?? '');
        $body    = strtolower(strip_tags($email['body'] ?? ''));
        $from    = strtolower($email['from'] ?? '');

        /* =========================
           1. URGENCY KEYWORDS
        ========================== */
        $urgentWords = [
            'urgent', 'segera', 'akun anda', 'account suspended',
            'verify now', 'click immediately', 'limited time',
            'act now', 'security alert'
        ];

        foreach ($urgentWords as $word) {
            if (str_contains($subject, $word) || str_contains($body, $word)) {
                $score += 15;
                $reasons[] = "Urgency keyword detected: {$word}";
            }
        }

        /* =========================
           2. SUSPICIOUS LINKS
        ========================== */
        preg_match_all('/https?:\/\/[^\s"]+/i', $body, $matches);
        $links = $matches[0] ?? [];

        foreach ($links as $link) {
            if (
                str_contains($link, 'bit.ly') ||
                str_contains($link, 'tinyurl') ||
                str_contains($link, 'rb.gy')
            ) {
                $score += 20;
                $reasons[] = "Shortened URL detected";
            }

            if (!str_contains($link, '.com') && !str_contains($link, '.id')) {
                $score += 10;
                $reasons[] = "Unusual domain in link";
            }
        }

        /* =========================
           3. CREDENTIAL REQUEST
        ========================== */
        $credentialWords = [
            'password', 'otp', 'kode verifikasi',
            'login', 'sign in', 'update account'
        ];

        foreach ($credentialWords as $word) {
            if (str_contains($body, $word)) {
                $score += 20;
                $reasons[] = "Credential request detected: {$word}";
            }
        }

        /* =========================
           4. SENDER DOMAIN CHECK
        ========================== */
        if (preg_match('/<(.+?)>/', $from, $match)) {
            $emailAddress = $match[1];
            $domain = substr(strrchr($emailAddress, "@"), 1);

            if (
                str_contains($subject, 'bca') && !str_contains($domain, 'bca')
            ) {
                $score += 25;
                $reasons[] = "Sender domain mismatch with brand";
            }
        }

        /* =========================
           FINAL LABEL
        ========================== */
        $label = match (true) {
            $score >= 60 => 'phishing',
            $score >= 30 => 'suspicious',
            $score > 0   => 'safe',
            default      => 'unknown',
        };

        return [
            'label'   => $label,
            'score'   => min($score, 100),
            'reasons' => array_unique($reasons),
        ];

        return [
            'label' => 'phishing',        // safe | suspicious | phishing
            'score' => 82,                // 0–100
            'rules' => [
                'contains_suspicious_link',
                'urgent_language',
                'spoofed_sender',
            ],
            'links' => [
                'http://bit.ly/free-login',
                'http://secure-update-login.com'
            ]
        ];

    }
}
