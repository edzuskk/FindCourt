<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReaction;
use App\Models\CourtReview;
use App\Services\ImageUploadTransaction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourtReviewController extends Controller
{
    public function __construct(private ImageUploadTransaction $imageUploads) {}

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
                'address' => $court->address,
                'city' => $court->city,
                'state' => $court->state,
                'description' => $court->description,
                'photo' => $court->photo,
                'latitude' => $court->latitude,
                'longitude' => $court->longitude,
                'created_at' => $court->created_at,
                'likes' => $court->likes,
                'dislikes' => $court->dislikes,
                'rating' => $court->rating,
                'avg_rating' => $court->rating,
                'reviews_count' => $reviews->count(),
            ],
            'reviews' => $reviews,
        ]);
    }

    public function store(Request $request, Court $court)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = Auth::user();

        $review = $this->imageUploads->persist(
            $request->file('photo'),
            'reviews',
            function (?string $photoPath) use ($court, $user, $validated): CourtReview {
                $lockedCourt = $court->lockForAggregateUpdate();

                if ($lockedCourt->reviews()->where('user_id', $user->id)->exists()) {
                    throw new HttpResponseException(
                        response()->json(['message' => 'You already reviewed this court.'], 422)
                    );
                }

                $review = CourtReview::create([
                    'court_id' => $lockedCourt->id,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'rating' => $validated['rating'] ?? null,
                    'comment' => $validated['comment'] ?? null,
                    'photo' => $photoPath,
                ]);

                $lockedCourt->recalculateRating();

                return $review;
            }
        );

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
        $isAdmin = Auth::user()->is_admin == 1;
        abort_unless($review->user_id === Auth::id() || $isAdmin, 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $review = $this->imageUploads->persist(
            $request->file('photo'),
            'reviews',
            function (?string $photoPath) use ($review, $court, $validated): CourtReview {
                $lockedCourt = $court->lockForAggregateUpdate();
                $lockedReview = CourtReview::query()
                    ->whereKey($review->getKey())
                    ->where('court_id', $lockedCourt->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($photoPath !== null) {
                    $validated['photo'] = $photoPath;
                }

                $lockedReview->update($validated);
                $lockedCourt->recalculateRating();

                return $lockedReview;
            },
            $review->photo
        );

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
        abort_unless($review->user_id === Auth::id() || Auth::user()->is_admin == 1, 403);

        $this->imageUploads->deleteAfter(function () use ($court, $review): void {
            $lockedCourt = $court->lockForAggregateUpdate();
            $lockedReview = CourtReview::query()
                ->whereKey($review->getKey())
                ->where('court_id', $lockedCourt->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReview->delete();
            $lockedCourt->recalculateRating();
        }, [$review->photo]);

        return response()->json(['success' => true]);
    }

    public function react(Request $request, Court $court)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'You must be logged in to react.'], 401);
        }

        $validated = $request->validate([
            'reaction' => ['required', 'in:like,dislike'],
        ]);

        $reaction = $validated['reaction'];

        $counts = DB::transaction(function () use ($court, $reaction): array {
            $lockedCourt = $court->lockForAggregateUpdate();

            CourtReaction::updateOrCreate(
                ['court_id' => $lockedCourt->id, 'user_id' => Auth::id()],
                ['reaction' => $reaction]
            );

            $lockedCourt->likes = CourtReaction::where('court_id', $lockedCourt->id)
                ->where('reaction', 'like')
                ->count();
            $lockedCourt->dislikes = CourtReaction::where('court_id', $lockedCourt->id)
                ->where('reaction', 'dislike')
                ->count();
            $lockedCourt->save();

            return [
                'likes' => $lockedCourt->likes,
                'dislikes' => $lockedCourt->dislikes,
            ];
        });

        return response()->json([
            'success' => true,
            ...$counts,
            'reaction' => $reaction,
        ]);
    }
}
