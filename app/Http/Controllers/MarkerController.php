<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MarkerController extends Controller
{
    public function index(Request $request)
    {
        $courts = Court::with('reviews.user')->get();
        $savedCourtIds = Auth::check()
            ? Auth::user()->savedCourts()->pluck('courts.id')->all()
            : [];

        $courts->transform(function ($court) {
            $court->avg_rating = $court->rating ?? ($court->reviews->avg('rating') ?: 0);
            return $court;
        });

        $courts->each(function ($court) use ($savedCourtIds) {
            $court->is_saved = in_array($court->id, $savedCourtIds, true);
        });

        if ($request->expectsJson() || $request->is('courts')) {
            return response()->json($courts);
        }

        return view('map', compact('courts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'description' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('courts', 'public');
        }

        $validated['user_id'] = Auth::id();
        $validated['username'] = Auth::user()?->username;

        $court = Court::create($validated);

        return response()->json([
            'success' => true,
            'court' => $court,
        ]);
    }
    public function update(Request $request, Court $court)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('photo')) {
            $oldPhoto = $court->photo;
            $validated['photo'] = $request->file('photo')->store('courts', 'public');
        } else {
            $oldPhoto = null;
        }

        $court->update($validated);

        if ($oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return response()->json([
            'success' => true,
            'court' => $court,
        ]);
    }
    public function destroy(Court $court)
    {
        if ($court->photo) {
            Storage::disk('public')->delete($court->photo);
        }

        foreach ($court->reviews as $review) {
            if ($review->photo) {
                Storage::disk('public')->delete($review->photo);
            }
        }

        $court->delete();

        return response()->json([
            'success' => true,
            'message' => 'Court deleted successfully.',
        ]);
    }
}
