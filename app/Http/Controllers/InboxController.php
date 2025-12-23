<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Services\GmailService;
use App\Models\Message;

class InboxController extends Controller
{
    public function sync()
    {
        $user = Auth::user();

        // 1. Pastikan Gmail connected
        if (!$user->google_token) {
            return redirect()->back()->with('error', 'Gmail not connected');
        }

        $gmail = new GmailService($user);

        // 2. Ambil inbox (5 email terbaru)
        $messages = $gmail->fetchInbox(5);

        foreach ($messages as $msg) {
            $gmailMessageId = $msg->getId();

            // 3. Cegah duplikasi
            if (Message::where('gmail_message_id', $gmailMessageId)->exists()) {
                continue;
            }

            // 4. Ambil isi email
            $data = $gmail->getFullMessage($gmailMessageId);

            if (!$data['body']) continue;

            Message::create([
            'user_id' => $user->id,
            'gmail_message_id' => $gmailMessageId,
            'from' => $meta['from'],
            'subject' => $meta['subject'],
            'snippet' => $meta['snippet'],
            'body' => $content, // ⬅️ FULL BODY
            'is_analyzed' => false,
        ]);


            if (!$body) {
                continue;
            }

            // 5. Simpan ke database (SESUIAI STRUKTUR TABEL)
            Message::create([
                'user_id'          => $user->id,
                'gmail_message_id' => $gmailMessageId,
                'from'             => $meta['from'] ?? 'Unknown',
                'subject'          => $meta['subject'] ?? '(No subject)',
                'snippet'          => $meta['snippet'] ?? null,
                'body'             => $body,
                'is_analyzed'      => false,
            ]);
        }

        return redirect()->back()->with('success', 'Inbox synced successfully');
    }

    public function index()
    {
        $messages = Message::where('user_id', auth()->id())
            ->latest()
            ->take(50)
            ->get();

        return view('inbox.index', compact('messages'));
    }

    public function show(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        return view('inbox.show', compact('message'));
    }
}
