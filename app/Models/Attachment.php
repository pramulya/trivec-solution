<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'attachment_id',
        'content_id',
        'filename',
        'mime_type',
        'path',
        'size',
    ];
}
