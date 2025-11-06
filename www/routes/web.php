<?php

use App\Http\Controllers\Auth\LoginController;
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

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::delete('/logout', [LoginController::class, 'logout'])
        ->name('logout');
});
