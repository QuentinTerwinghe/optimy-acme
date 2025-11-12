<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Campaign\CampaignController;
use App\Http\Controllers\Payment\PaymentMethodController;
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

    // Dashboard Statistics Routes
    Route::get('/campaigns/stats/total-funds-raised', [CampaignController::class, 'getTotalFundsRaised'])
        ->name('api.campaigns.stats.total-funds-raised');
    Route::get('/campaigns/stats/completed-count', [CampaignController::class, 'getCompletedCampaignsCount'])
        ->name('api.campaigns.stats.completed-count');
    Route::get('/campaigns/stats/fundraising-progress', [CampaignController::class, 'getFundraisingProgress'])
        ->name('api.campaigns.stats.fundraising-progress');

    // Category and Tag Routes
    Route::get('/categories', [CampaignController::class, 'getCategories'])
        ->name('api.categories.index');
    Route::get('/tags', [CampaignController::class, 'getTags'])
        ->name('api.tags.index');

    // Payment Method Routes
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])
        ->name('api.payment-methods.index');
});
