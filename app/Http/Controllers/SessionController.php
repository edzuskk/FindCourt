<?php

namespace App\Http\Controllers;

use App\Services\ImageUploadTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function __construct(private ImageUploadTransaction $imageUploads) {}

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($validated)) {
            $request->session()->regenerate();

            return redirect('/');
        }

        throw ValidationException::withMessages([
            'email' => 'Nepareiz e-pasts vai parole',
        ]);
    }

    public function show()
    {
        $user = auth()->user()->load(['courts', 'reviews.court', 'savedCourts']);

        return view('profile.view', compact('user'));
    }

    public function edit()
    {
        $user = auth()->user();

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $emailIsChanging = $request->input('email') !== $user->email;

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore(auth()->id())],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(auth()->id())],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'current_password' => [Rule::requiredIf($emailIsChanging), 'current_password'],
        ]);

        $this->imageUploads->persist(
            $request->file('profile_picture'),
            'profiles',
            function (?string $photoPath) use ($user, $validated): void {
                $user->fill([
                    'username' => $validated['username'],
                    'email' => $validated['email'],
                ]);

                if ($photoPath !== null) {
                    $user->photo = $photoPath;
                }

                $user->save();
            },
            $user->photo
        );

        return redirect()->route('profile.view');
    }
}
