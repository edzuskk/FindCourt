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
        if ($request->expectsJson() || $request->is('courts')) {
            $bounds = $request->validate([
                'south' => ['nullable', 'numeric', 'between:-90,90', 'required_with:north,west,east'],
                'north' => ['nullable', 'numeric', 'between:-90,90', 'required_with:south,west,east'],
                'west' => ['nullable', 'numeric', 'between:-180,180', 'required_with:south,north,east'],
                'east' => ['nullable', 'numeric', 'between:-180,180', 'required_with:south,north,west'],
                'court_id' => ['nullable', 'integer', 'min:1'],
            ]);

            if (isset($bounds['south'], $bounds['north'], $bounds['west'], $bounds['east'])) {
                abort_if($bounds['south'] > $bounds['north'] || $bounds['west'] > $bounds['east'], 422);
            }

            $courtQuery = Court::query()
                ->with('user:id,username')
                ->withCount('reviews')
                ->withAvg('reviews', 'rating');

            if (isset($bounds['south'], $bounds['north'], $bounds['west'], $bounds['east'])) {
                $courtQuery
                    ->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                    ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
            }

            $courts = $courtQuery->latest()->limit(500)->get();

            if (isset($bounds['court_id']) && ! $courts->contains('id', (int) $bounds['court_id'])) {
                $requestedCourt = Court::query()
                    ->with('user:id,username')
                    ->withCount('reviews')
                    ->withAvg('reviews', 'rating')
                    ->find($bounds['court_id']);

                if ($requestedCourt !== null) {
                    $courts->push($requestedCourt);
                }
            }

            $savedCourtIds = Auth::check()
                ? array_fill_keys(Auth::user()->savedCourts()->whereIn('courts.id', $courts->modelKeys())->pluck('courts.id')->all(), true)
                : [];

            $courts->transform(function ($court) {
                $court->username = $court->user?->username ?? ($court->username ?? 'Unknown user');
                $court->avg_rating = $court->rating ?? $court->reviews_avg_rating ?? 0;

                return $court;
            });

            $courts->each(function ($court) use ($savedCourtIds) {
                $court->is_saved = isset($savedCourtIds[$court->id]);
            });

            return response()->json($courts);
        }

        $courts = Court::query()
            ->select(['city', 'state'])
            ->whereNotNull('city')
            ->orWhereNotNull('state')
            ->distinct()
            ->get();

        return view('map', compact('courts'));
    }

    public function store(Request $request)
    {
        foreach (['latitude', 'longitude'] as $coordinate) {
            $value = $request->input($coordinate);

            if (is_numeric($value)) {
                $request->merge([$coordinate => round((float) $value, 8)]);
            }
        }

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
