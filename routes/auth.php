<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

// Login is Google-only and restricted by the ALLOWED_EMAILS env var.
// Email/password registration and password resets are intentionally removed.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google.redirect');

    Route::get('auth/google/callback', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
