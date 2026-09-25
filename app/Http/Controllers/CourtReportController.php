<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReport;
use App\Models\CourtReview;
use App\Models\ReviewReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourtReportController extends Controller
{
    public function store(Request $request, Court $court): JsonResponse
    {
        $validated = $request->validate([
            'reportReason'  => ['required', 'in:' . implode(',', CourtReport::REASONS)],
            'reportComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyReported = CourtReport::where('court_id', $court->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'success' => false,
                'message' => 'You already reported this court.',
            ], 422);
        }

        CourtReport::create([
            'court_id' => $court->id,
            'user_id'  => Auth::id(),
            'reportReason'   => $validated['reportReason'],
            'reportComment'  => $validated['reportComment'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! The report was sent to the admins.',
        ]);
    }

    public function storeReviewReport(Request $request, CourtReview $review): JsonResponse
    {
        $validated = $request->validate([
            'reportReason'  => ['required', 'in:' . implode(',', ReviewReport::REASONS)],
            'reportComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyReported = ReviewReport::where('review_id', $review->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'success' => false,
                'message' => 'You already reported this review.',
            ], 422);
        }

        ReviewReport::create([
            'review_id'     => $review->id,
            'user_id'       => Auth::id(),
            'reportReason'  => $validated['reportReason'],
            'reportComment' => $validated['reportComment'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! The review report was sent to the admins.',
        ]);
    }

    public function resolveReviewReport(ReviewReport $report): JsonResponse
    {
        $report->update(['is_resolved' => true]);

        return response()->json(['success' => true]);
    }

    public function destroyReviewReport(ReviewReport $report): JsonResponse
    {
        $report->delete();

        return response()->json(['success' => true]);
    }

    public function resolve(CourtReport $report): JsonResponse
    {
        $report->update(['is_resolved' => true]);

        return response()->json(['success' => true]);
    }

    public function destroy(CourtReport $report): JsonResponse
    {
        $report->delete();

        return response()->json(['success' => true]);
    }
}
