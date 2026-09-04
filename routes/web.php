<?php

use App\Http\Controllers\MarkerController;
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
});

Route::get('/map', [App\Http\Controllers\MarkerController::class, 'index'])->name('map.page');
Route::get('/courts', [App\Http\Controllers\MarkerController::class, 'index'])->name('courts.index');
Route::post('/courts', [App\Http\Controllers\MarkerController::class, 'store'])->name('courts.store');