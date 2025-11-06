<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CampaignController;
use Illuminate\Support\Facades\Route;

// Protected API Routes - using web middleware for session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    // Campaign Routes
    Route::get('/campaigns/active', [CampaignController::class, 'getActiveCampaigns'])
        ->name('api.campaigns.active');
});
