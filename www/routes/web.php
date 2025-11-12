<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Campaign\CampaignController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Donation\DonationController;
use App\Http\Controllers\Payment\FakePaymentController;
use App\Http\Controllers\Payment\PaymentCallbackController;
use App\Http\Controllers\Payment\PaymentResultController;
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
    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])
        ->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->name('campaigns.store');
    Route::get('/campaigns/{id}', [CampaignController::class, 'show'])
        ->name('campaigns.show');
    Route::get('/campaigns/{id}/edit', [CampaignController::class, 'edit'])
        ->name('campaigns.edit');
    Route::put('/campaigns/{id}', [CampaignController::class, 'update'])
        ->name('campaigns.update');
    Route::post('/campaigns/{id}/validate', [CampaignController::class, 'validate'])
        ->name('campaigns.validate');
    Route::post('/campaigns/{id}/reject', [CampaignController::class, 'reject'])
        ->name('campaigns.reject');

    // Donation Routes
    Route::get('/campaigns/{campaignId}/donate', [DonationController::class, 'create'])
        ->name('donations.create');

    // Payment Routes (Fake Gateway - for testing/development)
    Route::get('/payment/fake/{payment}/{session?}', [FakePaymentController::class, 'show'])
        ->name('payment.fake.show');

    // Payment Callback Routes (handles callbacks from external payment services)
    // This route accepts both GET and POST requests as different gateways use different methods
    Route::match(['get', 'post'], '/payment/callback/{payment}', [PaymentCallbackController::class, 'handle'])
        ->name('payment.callback');

    // Payment Result Routes (display success/failure pages after payment)
    Route::get('/payment/{payment}/success', [PaymentResultController::class, 'success'])
        ->name('payment.success');
    Route::get('/payment/{payment}/failure', [PaymentResultController::class, 'failure'])
        ->name('payment.failure');
});
