<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtReport extends Model
{
    public const REASONS = ['closed', 'duplicate', 'unsafe', 'other'];

    protected $fillable = [
        'court_id',
        'user_id',
        'reason',
        'details',
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
