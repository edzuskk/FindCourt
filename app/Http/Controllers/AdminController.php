<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReport;
use App\Models\CourtReview;
use App\Models\ReviewReport;
use App\Models\User;
use App\Services\ImageUploadTransaction;

class AdminController extends Controller
{
    public function __construct(private ImageUploadTransaction $imageUploads) {}

    public function index()
    {
        $userCount = User::count();
        $courtCount = Court::count();
        $courtReviewCount = CourtReview::count();
        $openCourtReportCount = CourtReport::where('is_resolved', false)->count();
        $openReviewReportCount = ReviewReport::where('is_resolved', false)->count();

        $users = User::query()
            ->select(['id', 'username', 'email', 'is_admin', 'created_at'])
            ->withCount(['courts', 'reviews'])
            ->latest()
            ->simplePaginate(25, ['*'], 'usersPage');

        $courts = Court::query()
            ->select(['id', 'name', 'address', 'city', 'state', 'description', 'rating', 'created_at', 'user_id'])
            ->with(['user:id,username'])
            ->withCount('reviews')
            ->latest()
            ->simplePaginate(25, ['*'], 'courtsPage');

        $courtReviews = CourtReview::query()
            ->select(['id', 'court_id', 'user_id', 'username', 'rating', 'comment', 'created_at'])
            ->with(['court:id,name', 'user:id,username'])
            ->latest()
            ->simplePaginate(25, ['*'], 'reviewsPage');

        $reports = CourtReport::query()
            ->with(['court:id,name', 'user:id,username', 'resolvedBy:id,username'])
            ->latest()
            ->simplePaginate(25, ['*'], 'courtReportsPage');

        $reviewReports = ReviewReport::query()
            ->with(['review:id,username,comment,court_id,user_id', 'review.user:id,username', 'review.court:id,name', 'user:id,username', 'resolvedBy:id,username'])
            ->latest()
            ->simplePaginate(25, ['*'], 'reviewReportsPage');

        return view('admin.dashboard', compact(
            'users',
            'courts',
            'courtReviews',
            'reports',
            'reviewReports',
            'userCount',
            'courtCount',
            'courtReviewCount',
            'openCourtReportCount',
            'openReviewReportCount'
        ));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $this->imageUploads->deleteAfter(
            fn () => $user->delete(),
            [$user->photo]
        );

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
