<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login.form');
});

// Public Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login.form')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login')
    ->middleware('guest');

// Password Reset Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])
        ->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('password.update');
});

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::delete('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    // Campaign Routes
    Route::get('/campaigns/create', [CampaignController::class, 'create'])
        ->name('campaigns.create');
});
