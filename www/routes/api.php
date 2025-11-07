<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Public Authentication API Routes
Route::middleware(['web'])->group(function () {
    Route::post('/login', [LoginController::class, 'login'])
        ->name('api.login');

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('api.logout');

    Route::get('/user', function () {
        return response()->json(auth()->user());
    })->middleware('auth');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('api.password.email');

    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('api.password.update');
});

// Protected API Routes - using web middleware for session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    // Campaign Routes
    Route::get('/campaigns/active', [CampaignController::class, 'getActiveCampaigns'])
        ->name('api.campaigns.active');
    Route::get('/campaigns/active/count', [CampaignController::class, 'getActiveCampaignsCount'])
        ->name('api.campaigns.active.count');
});
