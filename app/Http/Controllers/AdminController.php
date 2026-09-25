<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReport;
use App\Models\CourtReview;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Http\Request;

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

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
