<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReport;
use App\Models\CourtReview;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select(['id', 'username', 'email', 'is_admin', 'created_at'])
            ->withCount(['courts', 'reviews'])
            ->latest()
            ->get();

        $courts = Court::query()
            ->select(['id', 'name', 'address', 'city', 'state', 'description', 'rating', 'created_at', 'user_id'])
            ->with(['user:id,username'])
            ->withCount('reviews')
            ->latest()
            ->get();

        $courtReviews = CourtReview::query()
            ->select(['id', 'court_id', 'user_id', 'username', 'rating', 'comment', 'created_at'])
            ->with(['court:id,name', 'user:id,username'])
            ->latest()
            ->get();

        $reports = CourtReport::query()
            ->with(['court:id,name', 'user:id,username'])
            ->latest()
            ->get();

        $reviewReports = ReviewReport::query()
            ->with(['review:id,username,comment,court_id', 'review.court:id,name', 'user:id,username'])
            ->latest()
            ->get();

        return view('admin.dashboard', compact('users', 'courts', 'courtReviews', 'reports', 'reviewReports'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $ownedCourts = $user->courts()->with('reviews:id,court_id,photo')->get(['id', 'photo']);

        foreach ($ownedCourts as $court) {
            if ($court->photo) {
                Storage::disk('public')->delete($court->photo);
            }

            foreach ($court->reviews as $review) {
                if ($review->photo) {
                    Storage::disk('public')->delete($review->photo);
                }
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
