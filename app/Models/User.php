<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password', 'is_admin', 'photo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourtReview::class);
    }

    public function savedCourts(): BelongsToMany
    {
        return $this->belongsToMany(Court::class, 'saved_courts');
    }

    public function reportedCourts(): BelongsToMany
    {
        return $this->belongsToMany(Court::class, 'court_reports', 'user_id', 'court_id')
            ->withPivot('created_at')
            ->distinct();
    }
}
