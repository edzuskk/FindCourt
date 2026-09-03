<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('map');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [App\Http\Controllers\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [App\Http\Controllers\RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [App\Http\Controllers\SessionController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\SessionController::class, 'store'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\SessionController::class, 'destroy'])->name('logout');
});

Route::get('/create', [App\Http\Controllers\MarkerController::class, 'create'])->name('marker.create');