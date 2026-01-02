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
        'ai_score',
        'user_id',
        'direction',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    protected $casts = [
        'received_at' => 'datetime',
        'ai_score' => 'float',
    ];
}
