<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReport extends Model
{
    public const REASONS = ['spam', 'inappropriate', 'false information', 'other'];

    protected $fillable = [
        'comment_id',
        'user_id',
        'reportReason',
        'reportComment',
        'is_resolved',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
