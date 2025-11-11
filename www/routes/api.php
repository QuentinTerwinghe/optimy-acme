<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Campaign\CampaignController;
use Illuminate\Support\Facades\Route;

// Protected API Routes - using web middleware for session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    // Campaign Routes
    Route::get('/campaigns/active', [CampaignController::class, 'getActiveCampaigns'])
        ->name('api.campaigns.active');
    Route::get('/campaigns/active/count', [CampaignController::class, 'getActiveCampaignsCount'])
        ->name('api.campaigns.active.count');
    Route::get('/campaigns/manage', [CampaignController::class, 'getCampaignsForManagement'])
        ->name('api.campaigns.manage');

    // Category and Tag Routes
    Route::get('/categories', [CampaignController::class, 'getCategories'])
        ->name('api.categories.index');
    Route::get('/tags', [CampaignController::class, 'getTags'])
        ->name('api.tags.index');
});
