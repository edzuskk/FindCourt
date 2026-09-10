<?php

namespace App\Http\Controllers;
use App\Models\Court;

use Illuminate\Http\Request;

class MarkerController extends Controller
{
    public function index(Request $request)
    {
        $courts = Court::with('reviews.user')->get();

        $courts->transform(function ($court) {
            $court->avg_rating = $court->reviews->avg('rating') ?: 0;
            return $court;
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
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'description' => ['nullable', 'string'],
            'likes' => ['nullable', 'integer', 'min:0'],
            'dislikes' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('courts', 'public');
        }

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
            $validated['photo'] = $request->file('photo')->store('courts', 'public');
        }

        $court->update($validated);

        return response()->json([
            'success' => true,
            'court' => $court,
        ]);
    }
    public function destroy(Court $court)
    {
        $court->delete();

        return response()->json([
            'success' => true,
            'message' => 'Court deleted successfully.',
        ]);
    }
}
