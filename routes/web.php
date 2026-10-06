<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CourtReportController;
use App\Http\Controllers\CourtReviewController;
use App\Http\Controllers\CourtViewController;
use App\Http\Controllers\MarkerController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SavedCourtController;
use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password as PasswordRule;

Route::get('/', [MarkerController::class, 'index'])->name('map');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [SessionController::class, 'show'])->name('profile.view');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::post('/courts', [MarkerController::class, 'store'])->name('courts.store');
    Route::post('/courts/{court}/reviews', [CourtReviewController::class, 'store'])->middleware('throttle:20,1')->name('courts.reviews.store');
    Route::put('/courts/{court}/reviews/{review}', [CourtReviewController::class, 'update'])->name('courts.reviews.update');
    Route::delete('/courts/{court}/reviews/{review}', [CourtReviewController::class, 'destroy'])->name('courts.reviews.destroy');
    Route::post('/courts/{court}/react', [CourtReviewController::class, 'react'])->middleware('throttle:60,1')->name('courts.react');
    Route::post('/courts/{court}/save', [SavedCourtController::class, 'toggle'])->name('courts.save');
    Route::post('/courts/{court}/report', [CourtReportController::class, 'store'])->middleware('throttle:10,1')->name('courts.report');
    Route::delete('/my-reports/{report}', [CourtReportController::class, 'cancel'])->middleware('throttle:10,1')->name('reports.cancel');
    Route::post('/reviews/{review}/report', [CourtReportController::class, 'storeReviewReport'])->middleware('throttle:10,1')->name('reviews.report');
    Route::get('/profile/edit', [SessionController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [SessionController::class, 'update'])->name('profile.update');
});

Route::get('/map', [MarkerController::class, 'index'])->name('map.page');
Route::get('/courts', [MarkerController::class, 'index'])->name('courts.index');
Route::get('/courts/{court}/reviews', [CourtReviewController::class, 'index'])->name('courts.reviews.index');
Route::get('/courts/view/{court}', [CourtViewController::class, 'show'])->name('courts.show');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::get('/', [AdminController::class, 'index'])
        ->name('admin.dashboard');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
    Route::delete('/courts/{court}', [MarkerController::class, 'destroy'])->name('courts.destroy');
    Route::put('/courts/{court}', [MarkerController::class, 'update'])->name('courts.update');
    Route::patch('/reports/{report}', [CourtReportController::class, 'resolve'])->name('reports.resolve');
    Route::delete('/reports/{report}', [CourtReportController::class, 'destroy'])->name('reports.destroy');
    Route::patch('/review-reports/{report}', [CourtReportController::class, 'resolveReviewReport'])->name('review-reports.resolve');
    Route::delete('/review-reports/{report}', [CourtReportController::class, 'destroyReviewReport'])->name('review-reports.destroy');
    Route::put('/courts/{court}/reviews/{review}', [CourtReviewController::class, 'update'])->name('admin.reviews.update');
    Route::delete('/courts/{court}/reviews/{review}', [CourtReviewController::class, 'destroy'])->name('admin.reviews.destroy');
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
        'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()->symbols()],
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
