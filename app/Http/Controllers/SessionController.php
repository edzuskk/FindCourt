<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function destroy()
    {
        auth()->logout();
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
            "email" => "Nepareiz e-pasts vai parole"
          ]);
    }

    public function show()
    {
        $user = auth()->user()->load(['courts', 'reviews.court']);
        return view('profile.view', compact('user'));
    }

    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = auth()->user();

        $user->fill([
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        if ($request->hasFile('profile_picture')) {
            $user->photo = $request->file('profile_picture')->store('profiles', 'public');
        }

        $user->save();

        return redirect()->route('profile.view');
    }
}
