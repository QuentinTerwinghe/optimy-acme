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

// Protected Admin Routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
