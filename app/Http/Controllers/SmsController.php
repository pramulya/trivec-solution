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
        try {
            $messages = $this->termii->fetchMessages();
            $count = 0;
            // ... (keeping existing logic roughly, but optimizing for file edit size)
            // Re-implementing logic to ensure we don't lose it.
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

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => "Synced {$count} messages",
                    'count' => $count
                ]);
            }

            return back()->with('success', "Synced {$count} messages from Termii");
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['message' => 'Sync failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Sync failed');
        }
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
        
        if (request()->wantsJson()) {
            return response()->json($messages);
        }

        return view('sms.inbox', compact('messages'));
    }

    public function spam()
    {
        $messages = \App\Models\SmsMessage::where('user_id', auth()->id())
            ->where('ai_label', 'phishing')
            ->latest('received_at')
            ->get();
            
        if (request()->wantsJson()) {
            return response()->json($messages);
        }

        return view('sms.spam', compact('messages'));
    }

    public function sent()
    {
        $messages = \App\Models\SmsMessage::where('user_id', auth()->id())
            ->where('direction', 'outbound')
            ->latest('created_at')
            ->get(); 
            
        if (request()->wantsJson()) {
            return response()->json($messages);
        }

        return view('sms.sent', compact('messages'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            // Call Service
            $response = $this->termii->sendMessage($data['to'], $data['message']);

            // Save to DB
            $sms = \App\Models\SmsMessage::create([
                'user_id' => auth()->id(),
                'sender' => $data['to'], 
                'body' => $data['message'],
                'direction' => 'outbound',
                'source' => 'manual', 
                'received_at' => now(),
            ]);

            if (request()->wantsJson()) {
                return response()->json(['message' => 'SMS Sent!', 'data' => $sms]);
            }

            return back()->with('success', 'SMS queued for sending!');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['message' => 'Send failed: ' . $e->getMessage()], 422);
            }
            return back()->with('error', 'Send failed');
        }
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'sender' => 'required|string',
            'body' => 'required|string',
        ]);

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
            ], 'sms'); 

            if (isset($analysis[$sms->id])) {
                $sms->update([
                    'ai_label' => $analysis[$sms->id]['label'],
                    'ai_score' => $analysis[$sms->id]['score']
                ]);
            }
        }

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Message simulated!', 'data' => $sms]);
        }

        return back()->with('success', 'Message added and analyzed!');
    }
    public function destroy(\App\Models\SmsMessage $sms)
    {
        abort_if($sms->user_id !== auth()->id(), 403);
        $sms->delete();
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Deleted']);
        }
        return back()->with('success', 'SMS deleted');
    }

    public function handleWebhook(Request $request)
    {
        // 1. Validate (Loosely, to accept Termii's format)
        // Termii payload: { "sender": "...", "receiver": "...", "message": "...", ... }
        $data = $request->validate([
            'sender' => 'required|string',
            'receiver' => 'required|string',
            'message' => 'required|string',
        ]);

        // 2. Find User by their "Virtual Number" (receiver)
        // We match match the incoming 'receiver' (e.g., 628123...) with the user's saved 'phone_number'.
        $user = \App\Models\User::where('phone_number', $data['receiver'])->first();

        if (!$user) {
            \Log::warning("Incoming SMS for unknown receiver: " . $data['receiver']);
            return response()->json(['message' => 'User not found'], 200); // 200 to satisfy webhook
        }

        // 3. Store Message
        $sms = \App\Models\SmsMessage::create([
            'user_id' => $user->id,
            'sender' => $data['sender'],     // The customer who sent the SMS
            'body' => $data['message'],
            'direction' => 'inbound',
            'source' => 'termii',
            'received_at' => now(),
        ]);

        // 4. Trigger AI Analysis
        if ($user->ai_enabled) {
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

        return response()->json(['message' => 'Received']);
    }

    public function markAsSpam(\App\Models\SmsMessage $sms) 
    {
        abort_if($sms->user_id !== auth()->id(), 403);
        $sms->update(['ai_label' => 'phishing']); // Or a dedicated is_spam column if preferred, but existing logic uses ai_label='phishing' for spam folder.
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Marked as spam']);
        }
        return back()->with('success', 'Marked as spam');
    }
}
