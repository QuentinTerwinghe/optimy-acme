<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Campaign\CampaignController;
use App\Http\Controllers\Payment\PaymentMethodController;
use App\Http\Controllers\Payment\ProcessPaymentController;
use App\Http\Controllers\Role\RoleController;
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

    // Payment Processing Routes
    Route::post('/payments/initialize', [ProcessPaymentController::class, 'initialize'])
        ->name('api.payments.initialize');
});

// Admin-only API Routes - requires wildcard (*) permission
Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    // Role Management Routes
    Route::get('/roles', [RoleController::class, 'index'])
        ->name('api.admin.roles.index');
    Route::get('/roles/{id}', [RoleController::class, 'show'])
        ->name('api.admin.roles.show');
    Route::post('/roles', [RoleController::class, 'store'])
        ->name('api.admin.roles.store');
    Route::put('/roles/{id}', [RoleController::class, 'update'])
        ->name('api.admin.roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
        ->name('api.admin.roles.destroy');

    // Get all permissions (for role creation/editing)
    Route::get('/permissions', [RoleController::class, 'permissions'])
        ->name('api.admin.permissions.index');

    // Get all users (for role assignment)
    Route::get('/users', [RoleController::class, 'users'])
        ->name('api.admin.users.index');

    // Get users assigned to a specific role
    Route::get('/roles/{id}/users', [RoleController::class, 'roleUsers'])
        ->name('api.admin.roles.users');
});
