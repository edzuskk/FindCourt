<?php

namespace App\Http\Controllers;
use App\Models\Court;

use Illuminate\Http\Request;

class MarkerController extends Controller
{
    public function index(Request $request)
    {
        $courts = Court::all();

        // Return JSON for API requests
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
            'photo' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'description' => ['nullable', 'string'],
            'likes' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $court = Court::create($validated);

        return response()->json([
            'success' => true,
            'court' => $court,
        ]);

    }
    public function users()
    {
        return view('users');
    }
}
