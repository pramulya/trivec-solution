<?php

namespace App\Http\Controllers;

use App\Services\PhishingDetectionService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    protected $detector;
    protected $termii;

    public function __construct(PhishingDetectionService $detector, \App\Services\TermiiService $termii)
    {
        $this->detector = $detector;
        $this->termii = $termii;
    }

    public function sync()
    {
        $messages = $this->termii->fetchMessages();
        $count = 0;

        foreach ($messages as $msg) {
        // Simple duplication check
            $exists = \App\Models\SmsMessage::where('sender', $msg['sender'])
                ->where('body', $msg['sms'])
                ->where('user_id', auth()->id())
                ->exists();

            if (!$exists) {
                $sms = \App\Models\SmsMessage::create([
                    'user_id' => auth()->id(),
                    'sender' => $msg['sender'],
                    'body' => $msg['sms'],
                    'source' => 'termii',
                    'received_at' => $msg['date_created'],
                ]);

                // Trigger AI
                if (auth()->user()->ai_enabled) {
                    $analysis = $this->detector->analyzeBatch([
                        ['id' => $sms->id, 'body' => $sms->body]
                    ], 'sms');

                    if (isset($analysis[$sms->id])) {
                        $sms->update([
                            'ai_label' => $analysis[$sms->id]['label'],
                            'ai_score' => $analysis[$sms->id]['score']
                        ]);
                    }
                }
                $count++;
            }
        }

        return back()->with('success', "Synced {$count} messages from Termii");
    }

    public function inbox()
    {
        // Fetch from DB (Scoped to User) -> Exclude Spam
        $messages = \App\Models\SmsMessage::where('user_id', auth()->id())
            ->where(function($q) {
                $q->whereNull('ai_label')->orWhere('ai_label', '!=', 'phishing');
            })
            ->latest('received_at')
            ->get();
        return view('sms.inbox', compact('messages'));
    }

    public function spam()
    {
        $messages = \App\Models\SmsMessage::where('user_id', auth()->id())
            ->where('ai_label', 'phishing')
            ->latest('received_at')
            ->get();
        return view('sms.spam', compact('messages'));
    }

    public function sent()
    {
        $messages = \App\Models\SmsMessage::where('user_id', auth()->id())
            ->where('direction', 'outbound')
            ->latest('created_at')
            ->get(); 
        return view('sms.sent', compact('messages'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        // Call Service
        $response = $this->termii->sendMessage($data['to'], $data['message']);

        // Save to DB
        \App\Models\SmsMessage::create([
            'user_id' => auth()->id(),
            'sender' => $data['to'], // For outbound, sender is the recipient (or we can use a separate recipient column, but sticking to 'sender' as 'contact' for now is simpler for a prototype, OR better: use 'sender' as 'Me' and 'body' starts with 'To: ...' ? No, let's just reuse 'sender' as the 'other party phone number' to keep schema simple, adding a direction column clarifies who sent it.)
            // Actually, to be clearer: 'sender' column usually implies WHO SENT IT.
            // For OUTBOUND: Sender is ME, but better to store the DESTINATION in 'sender' and rely on 'direction=outbound' to know it was sent TO them.
            // Or better: Change schema to 'contact'. But for now, let's treat 'sender' as 'The Other Party'.
            'sender' => $data['to'], 
            'body' => $data['message'],
            'direction' => 'outbound',
            'source' => 'manual', // or 'termii'
            'received_at' => now(),
        ]);

        return back()->with('success', 'SMS queued for sending!');
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'sender' => 'required|string',
            'body' => 'required|string',
        ]);

        // 1. Create Record
        // 1. Create Record
        $sms = \App\Models\SmsMessage::create([
            'user_id' => auth()->id(),
            'sender' => $data['sender'],
            'body' => $data['body'],
            'source' => 'manual',
            'received_at' => now(),
        ]);

        // 2. Trigger AI Analysis (only if enabled)
        if (auth()->user()->ai_enabled) {
            $analysis = $this->detector->analyzeBatch([
                ['id' => $sms->id, 'body' => $sms->body]
            ], 'sms'); // Use SMS Model

            if (isset($analysis[$sms->id])) {
                $sms->update([
                    'ai_label' => $analysis[$sms->id]['label'],
                    'ai_score' => $analysis[$sms->id]['score']
                ]);
            }
        }

        return back()->with('success', 'Message added and analyzed!');
    }
}
