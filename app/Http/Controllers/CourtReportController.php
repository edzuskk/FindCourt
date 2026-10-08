<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReport;
use App\Models\CourtReview;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourtReportController extends Controller
{
    public function store(Request $request, Court $court): JsonResponse
    {
        $validated = $request->validate([
            'reportReason' => ['required', 'in:'.implode(',', CourtReport::REASONS)],
            'reportComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = DB::transaction(function () use ($court, $validated): bool {
            User::query()->whereKey(Auth::id())->lockForUpdate()->firstOrFail();

            if (CourtReport::where('court_id', $court->id)->where('user_id', Auth::id())->exists()) {
                return false;
            }

            CourtReport::create([
                'court_id' => $court->id,
                'user_id' => Auth::id(),
                'reportReason' => $validated['reportReason'],
                'reportComment' => $validated['reportComment'] ?? null,
            ]);

            return true;
        });

        if (! $created) {
            return response()->json([
                'success' => false,
                'message' => 'You already reported this court.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! The report was sent to the admins.',
        ]);
    }

    public function storeReviewReport(Request $request, CourtReview $review): JsonResponse
    {
        $validated = $request->validate([
            'reportReason' => ['required', 'in:'.implode(',', ReviewReport::REASONS)],
            'reportComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = DB::transaction(function () use ($review, $validated): bool {
            User::query()->whereKey(Auth::id())->lockForUpdate()->firstOrFail();

            if (ReviewReport::where('review_id', $review->id)->where('user_id', Auth::id())->exists()) {
                return false;
            }

            ReviewReport::create([
                'review_id' => $review->id,
                'user_id' => Auth::id(),
                'reportReason' => $validated['reportReason'],
                'reportComment' => $validated['reportComment'] ?? null,
            ]);

            return true;
        });

        if (! $created) {
            return response()->json([
                'success' => false,
                'message' => 'You already reported this review.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! The review report was sent to the admins.',
        ]);
    }

    public function resolveReviewReport(ReviewReport $report): JsonResponse
    {
        $report->update([
            'is_resolved' => true,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyReviewReport(ReviewReport $report): JsonResponse
    {
        $report->delete();

        return response()->json(['success' => true]);
    }

    public function resolve(CourtReport $report): JsonResponse
    {
        $report->update([
            'is_resolved' => true,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function cancel(CourtReport $report): JsonResponse
    {
        abort_unless($report->user_id === Auth::id(), 403);

        if ($report->is_resolved) {
            return response()->json([
                'success' => false,
                'message' => 'This report was already handled and cannot be cancelled.',
            ], 422);
        }

        $report->delete();

        return response()->json(['success' => true, 'message' => 'Report cancelled.']);
    }

    public function destroy(CourtReport $report): JsonResponse
    {
        $report->delete();

        return response()->json(['success' => true]);
    }
}
