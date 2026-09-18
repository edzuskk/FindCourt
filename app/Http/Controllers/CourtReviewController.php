<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReaction;
use App\Models\CourtReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourtReviewController extends Controller
{
    public function index(Court $court)
    {
        $reviews = $court->reviews()->with('user')->latest()->get()->map(function ($review) {
            return [
                'id' => $review->id,
                'is_owner' => Auth::id() === $review->user_id,
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
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($court->reviews()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You already reviewed this court.'], 422);
        }

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('reviews', 'public');
        }

        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'photo' => $validated['photo'] ?? null,
        ]);

        $ratedReviews = $court->reviews()->whereNotNull('rating');
        $court->rating = $ratedReviews->count() > 0 ? round($ratedReviews->avg('rating'), 2) : 0;
        $court->save();

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

    public function update(Request $request, Court $court, CourtReview $review)
    {
        abort_unless($review->court_id === $court->id, 404);
        abort_unless($review->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $oldPhoto = $review->photo;
            $validated['photo'] = $request->file('photo')->store('reviews', 'public');
        } else {
            $oldPhoto = null;
        }

        $review->update($validated);

        if ($oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $ratedReviews = $court->reviews()->whereNotNull('rating');
        $court->rating = $ratedReviews->count() > 0 ? round($ratedReviews->avg('rating'), 2) : 0;
        $court->save();

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

    public function destroy(Court $court, CourtReview $review)
    {
        abort_unless($review->court_id === $court->id, 404);
        abort_unless($review->user_id === Auth::id(), 403);

        if ($review->photo) {
            Storage::disk('public')->delete($review->photo);
        }

        $review->delete();

        $ratedReviews = $court->reviews()->whereNotNull('rating');
        $court->rating = $ratedReviews->count() > 0 ? round($ratedReviews->avg('rating'), 2) : 0;
        $court->save();

        return response()->json(['success' => true]);
    }

    public function react(Request $request, Court $court)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'You must be logged in to react.'], 401);
        }

        $validated = $request->validate([
            'reaction' => ['required', 'in:like,dislike'],
        ]);

        $reaction = $validated['reaction'];

        CourtReaction::updateOrCreate(
            ['court_id' => $court->id, 'user_id' => Auth::id()],
            ['reaction' => $reaction]
        );

        $court->likes = CourtReaction::where('court_id', $court->id)->where('reaction', 'like')->count();
        $court->dislikes = CourtReaction::where('court_id', $court->id)->where('reaction', 'dislike')->count();
        $court->save();

        return response()->json([
            'success' => true,
            'likes' => $court->likes,
            'dislikes' => $court->dislikes,
            'reaction' => $reaction,
        ]);
    }
}
