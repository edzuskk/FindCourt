<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReview;
use App\Services\ImageUploadTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarkerController extends Controller
{
    public function __construct(private ImageUploadTransaction $imageUploads) {}

    public function index(Request $request)
    {
        $courts = Court::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();
        $savedCourtIds = Auth::check()
            ? array_fill_keys(Auth::user()->savedCourts()->pluck('courts.id')->all(), true)
            : [];

        $courts->transform(function ($court) {
            $court->avg_rating = $court->rating ?? $court->reviews_avg_rating ?? 0;

            return $court;
        });

        $courts->each(function ($court) use ($savedCourtIds) {
            $court->is_saved = isset($savedCourtIds[$court->id]);
        });

        if ($request->expectsJson() || $request->is('courts')) {
            return response()->json($courts);
        }

        return view('map', compact('courts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'description' => ['required', 'string'],
            'latitude' => [
                'required',
                'numeric',
                'between:55.6,58.1',
                Rule::unique('courts', 'latitude')->where(
                    fn ($query) => $query->where('longitude', $request->input('longitude'))
                ),
            ],
            'longitude' => ['required', 'numeric', 'between:20.9,28.3'],
        ]);

        $initialRating = $validated['rating'];
        unset($validated['rating']);
        $validated['user_id'] = Auth::id();
        $validated['username'] = Auth::user()?->username;
        $photo = $request->file('photo');
        unset($validated['photo']);

        try {
            $court = $this->imageUploads->persist(
                $photo,
                'courts',
                function (?string $photoPath) use ($validated, $initialRating): Court {
                    $court = Court::create([
                        ...$validated,
                        'photo' => $photoPath,
                        'rating' => 0,
                    ]);

                    CourtReview::create([
                        'court_id' => $court->id,
                        'user_id' => Auth::id(),
                        'username' => Auth::user()->username,
                        'rating' => $initialRating,
                    ]);

                    $court->recalculateRating();

                    return $court;
                }
            );
        } catch (QueryException $exception) {
            $duplicateLocationExists = Court::query()
                ->where('latitude', $validated['latitude'])
                ->where('longitude', $validated['longitude'])
                ->exists();

            if (! $duplicateLocationExists) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'latitude' => 'A court already exists at these coordinates.',
            ]);
        }

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

        $court = $this->imageUploads->persist(
            $request->file('photo'),
            'courts',
            function (?string $photoPath) use ($court, $validated): Court {
                if ($photoPath !== null) {
                    $validated['photo'] = $photoPath;
                }

                $court->update($validated);

                return $court;
            },
            $court->photo
        );

        return response()->json([
            'success' => true,
            'court' => $court,
        ]);
    }

    public function destroy(Court $court)
    {
        $photoPaths = array_filter([
            $court->photo,
            ...$court->reviews()->whereNotNull('photo')->pluck('photo')->all(),
        ]);

        $this->imageUploads->deleteAfter(
            fn () => $court->delete(),
            $photoPaths
        );

        return response()->json([
            'success' => true,
            'message' => 'Court deleted successfully.',
        ]);
    }
}
