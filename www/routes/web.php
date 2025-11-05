<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login.form');
});

// Public Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login.form')->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])->name('login')->middleware('guest');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
