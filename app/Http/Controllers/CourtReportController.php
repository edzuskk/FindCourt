<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourtReportController extends Controller
{
    public function store(Request $request, Court $court): JsonResponse
    {
        $validated = $request->validate([
            'reason'  => ['required', 'in:' . implode(',', CourtReport::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
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
            'reason'   => $validated['reason'],
            'details'  => $validated['details'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! The report was sent to the admins.',
        ]);
    }

    public function resolve(CourtReport $report): JsonResponse
    {
        $report->update(['is_resolved' => true]);

        return response()->json(['success' => true]);
    }
}
