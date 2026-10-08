<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavedCourtController extends Controller
{
    public function toggle(Court $court): JsonResponse
    {
        $isSaved = DB::transaction(function () use ($court): bool {
            $user = User::query()
                ->whereKey(Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->savedCourts()->whereKey($court->id)->exists()) {
                $user->savedCourts()->detach($court->id);

                return false;
            }

            $user->savedCourts()->attach($court->id);

            return true;
        });

        return response()->json([
            'success' => true,
            'is_saved' => $isSaved,
        ]);
    }
}
