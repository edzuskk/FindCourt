<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourtReviewController extends Controller
{
    public function index(Court $court)
    {
        $reviews = $court->reviews()->with('user')->latest()->get()->map(function ($review) {
            return [
                'id' => $review->id,
                'username' => $review->username ?? ($review->user?->username ?? 'Unknown user'),
                'rating' => $review->rating,
                'comment' => $review->comment,
                'photo' => $review->photo,
                'created_at' => $review->created_at?->format('M d, Y'),
            ];
        });

        return response()->json([
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'likes' => $court->likes,
                'dislikes' => $court->dislikes,
                'rating' => $court->rating,
            ],
            'reviews' => $reviews,
        ]);
    }

    public function store(Request $request, Court $court)
    {
        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $user?->id,
            'username' => $user?->username ?? 'Guest',
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'photo' => $validated['photo'] ?? null,
        ]);

        if (!empty($validated['rating'])) {
            $existingReviews = $court->reviews()->where('user_id', $user?->id)->whereNotNull('rating')->get();
            $totalRating = $court->reviews()->whereNotNull('rating')->sum('rating');
            $count = $court->reviews()->whereNotNull('rating')->count();

            if ($existingReviews->count() === 0) {
                $court->rating = $count > 0 ? round(($totalRating + $validated['rating']) / ($count + 1), 2) : (float) $validated['rating'];
                $court->save();
            }
        }

        return response()->json([
            'success' => true,
            'review' => [
                'id' => $review->id,
                'username' => $review->username,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'photo' => $review->photo,
            ],
        ]);
    }

    public function react(Request $request, Court $court)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'You must be logged in to react.'], 401);
        }

        $validated = $request->validate([
            'reaction' => ['required', 'in:like,dislike'],
        ]);

        $user = Auth::user();
        $reaction = $validated['reaction'];

        $court->increment($reaction === 'like' ? 'likes' : 'dislikes');

        return response()->json([
            'success' => true,
            'likes' => $court->likes,
            'dislikes' => $court->dislikes,
            'reaction' => $reaction,
        ]);
    }
}
