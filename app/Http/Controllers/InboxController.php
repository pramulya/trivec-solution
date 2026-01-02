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
        if (!$user->google_refresh_token) return back()->with('error', 'Please connect Gmail first');

        $folder = $request->input('folder', 'inbox');
        $labelId = $this->getLabelId($folder);

        try {
            $result = $this->syncMessages($user, $folder, $labelId, 50);
            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $this->autoSync('inbox');
        
        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'inbox')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('inbox.index', compact('messages'));
    }

    public function drafts()
    {
        $this->autoSync('drafts');

        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'drafts')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.drafts', compact('messages'));
    }

    public function sent()
    {
        $this->autoSync('sent');

        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'sent')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.sent', compact('messages'));
    }

    public function starred()
    {
        // $this->autoSync('starred'); // Optional: syncing 'STARRED' label might duplicate content if not careful, but let's keep it for now or rely on other syncs
        // Actually, let's keep autoSync but understand it might create copies if we aren't careful with generic sync logic.
        // For now, the VIEW issue is the priority.
        $this->autoSync('starred');

        $messages = Message::where('user_id', auth()->id())
            ->where('is_starred', true) // ✅ Correct logic
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.starred', compact('messages'));
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

    private function autoSync($folder)
    {
        $user = Auth::user();
        if ($user && $user->google_refresh_token) {
            try {
                // Auto-sync with smaller limit (20) for speed
                $this->syncMessages($user, $folder, $this->getLabelId($folder), 20);
            } catch (\Throwable $e) {
                // Silent fail for auto-sync
                \Log::error("Auto-sync failed for $folder: " . $e->getMessage());
            }
        }
    }

    private function syncMessages($user, $folder, $labelId, $limit = 50)
    {
        set_time_limit(60); 

        $gmail = new GmailService($user);
        $detector = new PhishingDetectionService();

        $messages = $gmail->fetchMessages($labelId, $limit); 
        
        $startTime = time();
        $maxExecutionTime = 55;
        $processed = 0;
        $skipped = 0;

        // 1. Pre-process
        $newMessagesData = [];
        foreach ($messages as $msg) {
             if (time() - $startTime >= $maxExecutionTime) break;

             $gmailMessageId = $msg->getId();

             if ($existing = Message::where('gmail_message_id', $gmailMessageId)->first()) {
                 // Optimization: If we are intentionally syncing STARRED, 
                 // we know this message is starred, so update it.
                 if ($labelId === 'STARRED' && !$existing->is_starred) {
                     $existing->update(['is_starred' => true]);
                     // We don't increment $processed because we didn't do a full AI analysis/insert
                     // but we effectively "synced" status.
                 }
                 $skipped++;
                 continue;
             }

             try {
                 $data = $gmail->getFullMessage($gmailMessageId);
                 if (empty($data['body'])) continue;
                 
                 $newMessagesData[] = [
                     'id' => $gmailMessageId,
                     'data' => $data 
                 ];
             } catch (\Throwable $e) {
                 continue;
             }
        }

        // 2. AI Analysis
        $analysisResults = [];
        if ($user->ai_enabled && count($newMessagesData) > 0) {
            $batchInput = array_map(function($item) {
                 return [
                     'id' => $item['id'],
                     'body' => strip_tags($item['data']['body'])
                 ];
            }, $newMessagesData);

            $analysisResults = $detector->analyzeBatch($batchInput);
        }

        // 3. Save
        foreach ($newMessagesData as $item) {
            $gmailMessageId = $item['id'];
            $data = $item['data'];
            $analysis = $analysisResults[$gmailMessageId] ?? null;

            $isStarred = in_array('STARRED', $data['labelIds'] ?? []);
            $isHtml = $data['is_html'] ?? false;
            $emailDate = $data['date'] ? \Carbon\Carbon::parse($data['date']) : now();

            Message::create([
                'user_id'          => $user->id,
                'folder'           => $folder,
                'is_starred'       => $isStarred,
                'gmail_message_id' => $gmailMessageId,
                'from'             => $data['from'] ?? 'Unknown',
                'subject'          => $data['subject'] ?? '(No subject)',
                'snippet'          => $data['snippet'] ?? null,
                'body'             => $data['body'],
                'email_date'       => $emailDate,
                'is_html'          => $isHtml,
                'phishing_label' => $analysis['label'] ?? null,
                'phishing_score' => $analysis['score'] ?? null,
                'is_analyzed' => $analysis !== null,
            ]);

            $processed++;
        }

        $message = "Synced {$processed} {$folder} message(s)";
        if ($skipped > 0) $message .= ", skipped {$skipped} duplicate(s)";
        
        return ['message' => $message, 'processed' => $processed];
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

    public function trash()
    {
        $this->autoSync('trash');

        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'trash')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('folders.trash', compact('messages'));
    }

    public function spam()
    {
        $this->autoSync('spam');

        $messages = Message::where('user_id', auth()->id())
            ->where('folder', 'spam')
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

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
