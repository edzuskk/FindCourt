<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\MarkerController::class, 'index'])->name('map');

Route::middleware('guest')->group(function () {
    Route::get('/register', [App\Http\Controllers\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [App\Http\Controllers\RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [App\Http\Controllers\SessionController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\SessionController::class, 'store'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\SessionController::class, 'show'])->name('profile.view');
    Route::post('/logout', [App\Http\Controllers\SessionController::class, 'destroy'])->name('logout');
    Route::post('/courts', [App\Http\Controllers\MarkerController::class, 'store'])->name('courts.store');
    Route::put('/courts/{court}', [App\Http\Controllers\MarkerController::class, 'update'])->name('courts.update');
    Route::post('/courts/{court}/reviews', [App\Http\Controllers\CourtReviewController::class, 'store'])->name('courts.reviews.store');
    Route::post('/courts/{court}/react', [App\Http\Controllers\CourtReviewController::class, 'react'])->name('courts.react');
    Route::get('/profile/edit', [App\Http\Controllers\SessionController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\SessionController::class, 'update'])->name('profile.update');
});

Route::get('/map', [App\Http\Controllers\MarkerController::class, 'index'])->name('map.page');
Route::get('/courts', [App\Http\Controllers\MarkerController::class, 'index'])->name('courts.index');
Route::get('/courts/{court}/reviews', [App\Http\Controllers\CourtReviewController::class, 'index'])->name('courts.reviews.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])
        ->name('admin.dashboard');
    Route::delete('/courts/{court}', [App\Http\Controllers\MarkerController::class, 'destroy'])->name('courts.destroy');
});