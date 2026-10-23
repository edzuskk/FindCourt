<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;

class CourtViewController extends Controller
{
    public function show(Request $request, Court $court)
    {
        $reviews = $court->reviews()->with('user')->latest()->paginate(10);

        $reviews->getCollection()->transform(function ($review) {
            return (object) [
                'id' => $review->id,
                'is_owner' => auth()->id() === $review->user_id,
                'username' => $review->user?->username ?? ($review->username ?? 'Unknown user'),
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
