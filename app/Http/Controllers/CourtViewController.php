<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;

class CourtViewController extends Controller
{
    public function show(Request $request, Court $court)
    {
        $court->load(['reviews' => fn ($query) => $query->with('user')->latest()]);

        $reviews = $court->reviews->map(function ($review) {
            return (object) [
                'id' => $review->id,
                'is_owner' => auth()->id() === $review->user_id,
                'username' => $review->username ?? ($review->user?->username ?? 'Unknown user'),
                'rating' => $review->rating,
                'comment' => $review->comment,
                'photo' => $review->photo,
                'created_at' => $review->created_at?->format('M d, Y'),
            ];
        });

        return view('court.view', [
            'court' => $court,
            'reviews' => $reviews,
        ]);
    }
}
