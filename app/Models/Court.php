<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Court extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'city',
        'state',
        'photo',
        'rating',
        'description',
        'likes',
        'dislikes',
        'username',
        'latitude',
        'longitude',
    ];

    public function reviews()
    {
        return $this->hasMany(CourtReview::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
