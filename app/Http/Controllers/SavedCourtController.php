<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SavedCourtController extends Controller
{
    public function toggle(Court $court): JsonResponse
    {
        $user = Auth::user();
        $isSaved = $user->savedCourts()->whereKey($court->id)->exists();

        if ($isSaved) {
            $user->savedCourts()->detach($court->id);
            $isSaved = false;
        } else {
            $user->savedCourts()->attach($court->id);
            $isSaved = true;
        }

        return response()->json([
            'success' => true,
            'is_saved' => $isSaved,
        ]);
    }
}
