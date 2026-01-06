<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ComposeController extends Controller
{
    public function index()
    {
        return view('compose');
    }

    public function send(Request $request, \App\Services\GmailService $gmail)
    {
        $data = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'file|max:10240', // Max 10MB per file
        ]);

        try {
            $gmail->sendEmail(
                $data['to'], 
                $data['subject'], 
                $data['body'],
                $request->allFiles()['attachments'] ?? []
            );
            return redirect()->route('inbox.index')->with('success', 'Email sent successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage())->withInput();
        }
    }
}
