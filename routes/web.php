<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

Route::get('/', [App\Http\Controllers\MarkerController::class, 'index'])->name('map');

Route::middleware('guest')->group(function () {
    Route::get('/register', [App\Http\Controllers\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [App\Http\Controllers\RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');
    Route::get('/login', [App\Http\Controllers\SessionController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\SessionController::class, 'store'])->middleware('throttle:10,1')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\SessionController::class, 'show'])->name('profile.view');
    Route::post('/logout', [App\Http\Controllers\SessionController::class, 'destroy'])->name('logout');
    Route::post('/courts', [App\Http\Controllers\MarkerController::class, 'store'])->name('courts.store');
    Route::post('/courts/{court}/reviews', [App\Http\Controllers\CourtReviewController::class, 'store'])->middleware('throttle:20,1')->name('courts.reviews.store');
    Route::put('/courts/{court}/reviews/{review}', [App\Http\Controllers\CourtReviewController::class, 'update'])->name('courts.reviews.update');
    Route::delete('/courts/{court}/reviews/{review}', [App\Http\Controllers\CourtReviewController::class, 'destroy'])->name('courts.reviews.destroy');
    Route::post('/courts/{court}/react', [App\Http\Controllers\CourtReviewController::class, 'react'])->middleware('throttle:60,1')->name('courts.react');
    Route::post('/courts/{court}/save', [App\Http\Controllers\SavedCourtController::class, 'toggle'])->name('courts.save');
    Route::post('/courts/{court}/report', [App\Http\Controllers\CourtReportController::class, 'store'])->middleware('throttle:10,1')->name('courts.report');
    Route::get('/profile/edit', [App\Http\Controllers\SessionController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\SessionController::class, 'update'])->name('profile.update');
});

Route::get('/map', [App\Http\Controllers\MarkerController::class, 'index'])->name('map.page');
Route::get('/courts', [App\Http\Controllers\MarkerController::class, 'index'])->name('courts.index');
Route::get('/courts/{court}/reviews', [App\Http\Controllers\CourtReviewController::class, 'index'])->name('courts.reviews.index');
Route::get('/courts/view/{court}', [App\Http\Controllers\CourtViewController::class, 'show'])->name('courts.show');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])
        ->name('admin.dashboard');
    Route::delete('/users/{user}', [App\Http\Controllers\AdminController::class, 'destroy'])->name('users.destroy');
    Route::delete('/courts/{court}', [App\Http\Controllers\MarkerController::class, 'destroy'])->name('courts.destroy');
    Route::put('/courts/{court}', [App\Http\Controllers\MarkerController::class, 'update'])->name('courts.update');
    Route::patch('/reports/{report}', [App\Http\Controllers\CourtReportController::class, 'resolve'])->name('reports.resolve');
    Route::delete('/reports/{report}', [App\Http\Controllers\CourtReportController::class, 'destroy'])->name('reports.destroy');
});

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('forgot-password');

Route::post('/forgot-password', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
    ]);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {
        return back()->with('status', 'Password reset link has been sent to your email.');
    }

    return back()->withErrors([
        'email' => 'We could not find an account with that email address.',
    ]);
})->middleware('throttle:5,1')->name('password.email');

Route::get('/reset-password/{token}', function (
    string $token,
    Request $request
) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => $request->email,
    ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {

    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $status = Password::reset(
        $request->only(
            'email',
            'password',
            'password_confirmation',
            'token'
        ),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return redirect('/login')
            ->with('status', 'Password successfully reset!');
    }

    return back()->withErrors([
        'email' => 'The password reset link is invalid or has expired.',
    ]);
})->middleware('throttle:5,1')->name('password.update');