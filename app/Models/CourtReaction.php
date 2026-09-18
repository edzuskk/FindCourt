<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourtReaction extends Model
{
    protected $fillable = [
        'court_id',
        'user_id',
        'reaction',
    ];
}