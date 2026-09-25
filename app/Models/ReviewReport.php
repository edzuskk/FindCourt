<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    public const REASONS = ['spam', 'inappropriate', 'false information', 'other'];

    protected $table = 'review_reports';

    protected $fillable = [
        'review_id',
        'user_id',
        'reportReason',
        'reportComment',
        'is_resolved',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(CourtReview::class, 'review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
