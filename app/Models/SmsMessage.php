<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $fillable = [
        'sender',
        'body',
        'source',
        'received_at',
        'ai_label',
        'ai_score'
    ];
    
    protected $casts = [
        'received_at' => 'datetime',
        'ai_score' => 'float',
    ];
}
