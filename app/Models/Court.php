<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
