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

        // Validasi Gmail
        if (!$user->google_refresh_token) {
            return back()->with('error', 'Please connect Gmail first');
        }

        // Determine Folder & Gmail Label
        // Map frontend folder names to Gmail Label IDs
        $folder = $request->input('folder', 'inbox');
        $labelId = match ($folder) {
            'drafts' => 'DRAFT',
            'sent'   => 'SENT',
            'starred'=> 'STARRED',
            'spam'   => 'SPAM',
            'trash'  => 'TRASH',
            default  => 'INBOX',
        };

        // Increase execution time limit for this request
        set_time_limit(60); // 60 seconds

        try {
            $gmail = new GmailService($user);
            $detector = new PhishingDetectionService();

            // Fetch based on Label ID
            $messages = $gmail->fetchMessages($labelId, 50); 
            
            $startTime = time();
            $maxExecutionTime = 55; // Leave 5 seconds buffer
            $processed = 0;
            $skipped = 0;



            // 1. Pre-process and collect new messages for AI analysis
            $newMessagesData = [];
            foreach ($messages as $msg) {
                 if (time() - $startTime >= $maxExecutionTime) break;

                 $gmailMessageId = $msg->getId();

                 // Skip if exists
                 if (Message::where('gmail_message_id', $gmailMessageId)->exists()) {
                     $skipped++;
                     continue;
                 }

                 try {
                     $data = $gmail->getFullMessage($gmailMessageId);
                     if (empty($data['body'])) continue;
                     
                     // Add to batch list
                     $newMessagesData[] = [
                         'id' => $gmailMessageId,
                         'data' => $data // Keep full data for saving later
                     ];
                 } catch (\Throwable $e) {
                     continue;
                 }
            }

            // 2. Batch Analyze with AI (if enabled)
            $analysisResults = [];
            if ($user->ai_enabled && count($newMessagesData) > 0) {
                $batchInput = array_map(function($item) {
                     return [
                         'id' => $item['id'],
                         // Strip tags to reduce payload size and match model expectation
                         'body' => strip_tags($item['data']['body'])
                     ];
                }, $newMessagesData);

                $analysisResults = $detector->analyzeBatch($batchInput);
                \Log::info("AI Results for " . count($batchInput) . " items:", $analysisResults);
            }

            // 3. Save Messages
            foreach ($newMessagesData as $item) {
                $gmailMessageId = $item['id'];
                $data = $item['data'];

                $analysis = $analysisResults[$gmailMessageId] ?? null;

                // Check if starred
                $isStarred = in_array('STARRED', $data['labelIds'] ?? []);

                // Store whether body is HTML for proper rendering
                $isHtml = $data['is_html'] ?? false;

                // Parse email date
                $emailDate = $data['date'] ? \Carbon\Carbon::parse($data['date']) : now();

                Message::create([
                    'user_id'          => $user->id,
                    'folder'           => $folder, // Store the folder logic
                    'is_starred'       => $isStarred,
                    'gmail_message_id' => $gmailMessageId,
                    'from'             => $data['from'] ?? 'Unknown',
                    'subject'          => $data['subject'] ?? '(No subject)',
                    'snippet'          => $data['snippet'] ?? null,
                    'body'             => $data['body'],
                    'email_date'       => $emailDate,
                    'is_html'          => $isHtml,

                    // 🧠 PHISHING (AI BATCH RESULT)
                    'phishing_label' => $analysis['label'] ?? null,
                    'phishing_score' => $analysis['score'] ?? null,
                    'is_analyzed' => $analysis !== null,
                ]);

                $processed++;
            }

            $message = "Synced {$processed} {$folder} message(s)";
            if ($skipped > 0) {
                $message .= ", skipped {$skipped} duplicate(s)";
            }
            if (time() - $startTime >= $maxExecutionTime) {
                $message .= ". Time limit reached - click Sync again to continue.";
            }

            return back()->with('success', $message);

        } catch (\Throwable $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }


    public function index()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'inbox') // Default to inbox
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('inbox.index', compact('messages'));
    }

    public function drafts()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'drafts')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.drafts', compact('messages'));
    }

    public function sent()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'sent')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.sent', compact('messages'));
    }

    public function starred()
    {
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'starred')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.starred', compact('messages'));
    }

    public function show(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        $rules = json_decode($message->phishing_rules ?? '[]', true);

        preg_match_all('/https?:\/\/[^\s"<]+/i', $message->body, $matches);
        $links = $matches[0] ?? [];

        return view('inbox.show', compact('message', 'rules', 'links'));
    }

    public function toggleStar(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        try {
            $user = Auth::user();
            $gmail = new GmailService($user);

            // Toggle local
            $newState = !$message->is_starred;
            $message->update(['is_starred' => $newState]);

            // Sync to Gmail
            $gmail->toggleStar($message->gmail_message_id, $newState);

            return back()->with('success', $newState ? 'Message starred' : 'Message unstarred');

        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to update star: ' . $e->getMessage());
        }
    }
}
