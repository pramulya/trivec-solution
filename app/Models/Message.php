<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder',
        'is_starred',
        'gmail_message_id',
        'from',
        'subject',
        'snippet',
        'body',
        'email_date',
        'is_html',
        'is_analyzed',
        'phishing_label',
        'phishing_score',
        'phishing_rules',
        'phishing_reasons',
    ];

    protected $casts = [
        'email_date' => 'datetime',
        'is_starred' => 'boolean',
    ];



    public function getSanitizedBodyAttribute(): string
    {
        $sanitizer = new \App\Services\EmailBodySanitizer();
        
        // Determine if AI is enabled for the owner of this message
        // Since we are in the model, we can check the user relation or auth() if strictly for display to current user
        // But for correctness, it should depend on the message owner's setting? 
        // Actually the Requirement says "AI Mode" is a User Toggle.
        // Let's assume we use the current user's preference or the message owner's. 
        // The View used `auth()->user()->ai_enabled`.
        
        $aiEnabled = auth()->check() ? auth()->user()->ai_enabled : false;

        $body = $this->body;
        
        // If it's plain text, convert to HTML first
        if (!$this->is_html) {
            $body = nl2br(e($body));
        }

        return $sanitizer->clean(
            $body, 
            $aiEnabled,
            $this->phishing_label
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'message_id', 'gmail_message_id');
    }
}
