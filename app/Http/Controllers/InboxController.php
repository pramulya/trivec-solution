<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GmailService;
use App\Models\Message;
use App\Services\PhishingDetectionService;

class InboxController extends Controller
{
    public function sync()
    {
        $user = Auth::user();

        // Validasi Gmail
        if (!$user->google_refresh_token) {
            return back()->with('error', 'Please connect Gmail first');
        }

        try {
            $gmail = new GmailService($user);
            $detector = new PhishingDetectionService();

            $messages = $gmail->fetchInbox(1000);

            foreach ($messages as $msg) {
                $gmailMessageId = $msg->getId();

                // Skip duplikat
                if (Message::where('gmail_message_id', $gmailMessageId)->exists()) {
                    continue;
                }

                $data = $gmail->getFullMessage($gmailMessageId);

                if (empty($data['body'])) {
                    continue;
                }

                // DEFAULT: AI OFF
                $analysis = null;

                // ✅ HANYA JALANKAN AI JIKA AKTIF
                if ($user->ai_enabled) {
                    $analysis = $detector->analyze(
                        $data['subject'] ?? '',
                        $data['body']
                    );
                }

                Message::create([
                    'user_id'          => $user->id,
                    'gmail_message_id' => $gmailMessageId,
                    'from'             => $data['from'] ?? 'Unknown',
                    'subject'          => $data['subject'] ?? '(No subject)',
                    'snippet'          => $data['snippet'] ?? null,
                    'body'             => $data['body'],

                    // 🧠 PHISHING (AMAN WALAU AI OFF)
                    'phishing_label' => $analysis['label'] ?? null,
                    'phishing_score' => $analysis['score'] ?? null,
                    'phishing_rules' => isset($analysis['rules'])
                        ? json_encode($analysis['rules'])
                        : null,

                    'is_analyzed' => $analysis !== null,
                ]);
            }

            return back()->with('success', 'Inbox synced successfully');

        } catch (\Throwable $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }


    public function index()
    {
        $messages = Message::where('user_id', auth()->id())
            ->latest()
            ->paginate(50);

        return view('inbox.index', compact('messages'));
    }

    public function show(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        $rules = json_decode($message->phishing_rules ?? '[]', true);

        preg_match_all('/https?:\/\/[^\s"<]+/i', $message->body, $matches);
        $links = $matches[0] ?? [];

        return view('inbox.show', compact('message', 'rules', 'links'));
    }
}
