<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GmailService;
use App\Models\Message;
use App\Services\PhishingDetectionService;

class InboxController extends Controller
{
    public function sync(Request $request)
    {
        $user = Auth::user();
        
        // Handle connection check
        if (!$user->google_refresh_token) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Not connected'], 401);
            }
            return back()->with('error', 'Please connect Gmail first');
        }

        // NON-BLOCKING OPTIMIZATION:
        // Release the session lock immediately so the UI doesn't freeze while this runs.
        // The sync process takes 10-20s, we don't want the user waiting that long to switch pages.
        session()->save();

        $folder = $request->input('folder', 'inbox');
        $pageToken = $request->input('pageToken', null); // Support for crawling
        $labelId = $this->getLabelId($folder);

        try {
            // syncMessages now returns ['count' => X, 'nextPageToken' => Y]
            // Reduced batch size to 5 to prevent Nginx/PHP timeouts during demo
            $result = $this->syncMessages($user, $folder, $labelId, 5, $pageToken);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'count' => $result['count'],
                    'nextPageToken' => $result['nextPageToken'],
                    'folder' => $folder
                ]);
            }

            // For manual button click
            $msg = $result['count'] > 0 
                ? "Synced {$result['count']} new messages." 
                : "Folder is up to date.";
            return back()->with('success', $msg);

        } catch (\Throwable $e) {
            \Log::error("SYNC-DEBUG: Sync failed entirely: " . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    // --- HELPER METHODS ---

    private function getLabelId($folder) 
    {
        return match ($folder) {
            'drafts' => 'DRAFT',
            'sent'   => 'SENT',
            'starred'=> 'STARRED',
            'spam'   => 'SPAM',
            'trash'  => 'TRASH',
            default  => 'INBOX',
        };
    }
    
    private function syncMessages($user, $folder, $labelId, $limit = 20, $pageToken = null)
    {
        \Log::info("SYNC-DEBUG: Starting sync for user {$user->id}, folder: {$folder}, label: {$labelId}");

        // Prevent timeout during heavy AI/Network ops
        set_time_limit(120); 

        $gmail = new GmailService($user);
        
        // Fetch fetchPage from GmailService returns ['messages' => [], 'nextPageToken' => '...']
        $data = $gmail->fetchMessages($labelId, $limit, $pageToken);
        $gmailMessages = $data['messages'];

        \Log::info("SYNC-DEBUG: fetchMessages returned " . count($gmailMessages) . " messages.");

        $batchItems = [];
        $messagesDetails = [];
        $count = 0;

        foreach ($gmailMessages as $gmailMsg) {
            // Check if exists
            if (Message::where('gmail_message_id', $gmailMsg->getId())->exists()) {
                // \Log::info("SYNC-DEBUG: Skipping existing message " . $gmailMsg->getId());
                continue;
            }

            \Log::info("SYNC-DEBUG: Processing new message " . $gmailMsg->getId());

            // Get full details
            try {
                $details = $gmail->getFullMessage($gmailMsg->getId());
                
                // Prepare for Batch Analysis (Subject + Body)
                $analyticsText = $details['subject'] . "\n" . $details['body'];
                
                $batchItems[] = [
                    'id' => $gmailMsg->getId(),
                    'body' => $analyticsText
                ];

                $messagesDetails[$gmailMsg->getId()] = $details;

            } catch (\Throwable $e) {
                \Log::warning("Failed to fetch message details: " . $gmailMsg->getId());
            }
        }

        // Run Batch AI Analysis
        $aiResults = [];
        if (!empty($batchItems)) {
            \Log::info("SYNC-DEBUG: Running AI analysis on " . count($batchItems) . " items.");
            try {
                // Feature Flag to disable AI for demo speed if needed
                if (config('services.ai.enabled', true)) {
                    $phishing = new PhishingDetectionService();
                    $aiResults = $phishing->analyzeBatch($batchItems);
                    \Log::info("SYNC-DEBUG: AI analysis completed. Results count: " . count($aiResults));
                }
            } catch (\Throwable $e) {
                \Log::error("AI Analysis skipped/failed: " . $e->getMessage());
                // Continue without AI results (they will default to unknown)
            }
        } else {
             \Log::info("SYNC-DEBUG: No items to run AI analysis on.");
        }

        // Save to Database
        foreach ($messagesDetails as $gmailId => $details) {
            $analysis = $aiResults[$gmailId] ?? [
                'label' => 'pending', 
                'score' => null, 
                'rules' => []
            ];

            Message::create([
                'user_id' => $user->id,
                'gmail_message_id' => $gmailId,
                'folder' => $folder,
                'subject' => $details['subject'],
                'from' => $details['from'],
                'snippet' => $details['snippet'],
                'body' => $details['body'],
                'is_html' => $details['is_html'],
                'email_date' => date('Y-m-d H:i:s', strtotime($details['date'])),
                'is_starred' => in_array('STARRED', $details['labelIds']),
                'phishing_label' => $analysis['label'],
                'phishing_score' => $analysis['score'],
                'phishing_rules' => json_encode($analysis['rules'] ?? []),
            ]);
            $count++;
        }

        \Log::info("SYNC-DEBUG: Sync completed. Saved {$count} new messages.");

        return [
            'count' => $count, 
            'nextPageToken' => $data['nextPageToken']
        ];
    }
    
    public function show(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);
        
        $message->load('attachments');

        if (request()->wantsJson()) {
            return response()->json([
                'message' => $message,
                'formatted_date' => $message->email_date->format('M d, Y h:i A')
            ]);
        }
        
        return view('inbox.show', compact('message'));
    }

    public function index()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'inbox')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if (request()->wantsJson()) {
            return response()->json($messages);
        }
        return view('inbox.index', compact('messages'));
    }

    public function downloadAttachment($id)
    {
        $attachment = \App\Models\Attachment::findOrFail($id);
        $message = Message::where('gmail_message_id', $attachment->message_id)->first();

        // Security Check: Ensure user owns the message
        if (!$message || $message->user_id !== auth()->id()) {
            abort(403);
        }

        if (!\Storage::exists($attachment->path)) {
            abort(404, 'File not found on server');
        }

        return \Storage::download($attachment->path, $attachment->filename);
    }

    public function drafts()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'drafts')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if (request()->wantsJson()) {
            return response()->json($messages);
        }
        return view('folders.drafts', compact('messages'));
    }

    public function sent()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'sent')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if (request()->wantsJson()) {
            return response()->json($messages);
        }
        return view('folders.sent', compact('messages'));
    }

    public function starred()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('is_starred', true) 
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if (request()->wantsJson()) {
            return response()->json($messages);
        }
        return view('folders.starred', compact('messages'));
    }

    public function trash()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'trash')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if (request()->wantsJson()) {
            return response()->json($messages);
        }
        return view('folders.trash', compact('messages'));
    }

    public function spam()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'spam')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if (request()->wantsJson()) {
            return response()->json($messages);
        }
        return view('folders.spam', compact('messages'));
    }

    /* =====================
        ACTIONS
    ===================== */

    public function destroy(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);
        
        try {
            $gmail = new GmailService(auth()->user());
            $gmail->trashMessage($message->gmail_message_id);

            $message->update(['folder' => 'trash']);

            return redirect()->route('inbox.index')->with('success', 'Message moved to Trash');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    public function markAsSpam(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        try {
            $gmail = new GmailService(auth()->user());
            
            // Add SPAM, remove INBOX
            $gmail->modifyLabels($message->gmail_message_id, ['SPAM'], ['INBOX']);

            $message->update(['folder' => 'spam']);

            return redirect()->route('inbox.index')->with('success', 'Message reported as Spam');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to mark as spam: ' . $e->getMessage());
        }
    }
}
