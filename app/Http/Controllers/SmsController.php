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
                ->exists();

            if (!$exists) {
                $sms = \App\Models\SmsMessage::create([
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
        // Fetch from DB
        $messages = \App\Models\SmsMessage::latest('received_at')->get();
        return view('sms.inbox', compact('messages'));
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'sender' => 'required|string',
            'body' => 'required|string',
        ]);

        // 1. Create Record
        $sms = \App\Models\SmsMessage::create([
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
