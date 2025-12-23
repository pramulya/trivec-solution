<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAccount extends Model
{
    protected $fillable = [
        'user_id',
        'google_id',
        'access_token',
        'refresh_token',
        'expires_in',
        'token_type',
        'scope',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
