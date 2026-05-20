<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\OrderStatusController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\DeliveryCompanyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UploadController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    // Email login
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']); // Add this if you have registration

    // Password reset
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

    // Social authentication
    Route::get('/google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [SocialAuthController::class, 'googleCallback']);
    Route::get('/facebook/redirect', [SocialAuthController::class, 'facebookRedirect']);
    Route::get('/facebook/callback', [SocialAuthController::class, 'facebookCallback']);
});

// Public product routes (optional - if you want public access)
Route::get('/products/public', [ProductController::class, 'index']); // Public product listing
Route::get('/products/public/{product}', [ProductController::class, 'show']); // Public product view
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/tag/{tag}', [ProductController::class, 'getByTag']);
Route::get('/products-summary', [ProductController::class, 'summary']);

// Webhook routes (public - for carrier callbacks)
Route::post('/shipments/webhook/{companyId}', [ShipmentController::class, 'handleWebhook']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // User authentication routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    
    // Team management routes
    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::post('/members', [TeamController::class, 'storeMember']);
        Route::put('/members/{id}', [TeamController::class, 'updateMember']);
        Route::delete('/members/{id}', [TeamController::class, 'destroyMember']);
        Route::post('/settings', [TeamController::class, 'updateSettings']);
        Route::post('/impersonate/{id}', [TeamController::class, 'impersonate']);
    });
    
    // Order management routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::post('/{id}/assign', [OrderController::class, 'assign']);
    });
    
    // Shipment management routes
    Route::prefix('shipments')->group(function () {
        Route::get('/', [ShipmentController::class, 'index']);
        Route::post('/', [ShipmentController::class, 'store']);
        Route::get('/{id}', [ShipmentController::class, 'show']);
        Route::put('/{id}', [ShipmentController::class, 'update']);
        Route::delete('/{id}', [ShipmentController::class, 'destroy']);
        Route::get('/{id}/tracking', [ShipmentController::class, 'getTracking']);
    });

    // Delivery company management routes
    Route::prefix('companies')->group(function () {
        Route::get('/', [DeliveryCompanyController::class, 'index']);
        Route::get('/{id}', [DeliveryCompanyController::class, 'show']);
        Route::post('/{id}/connect', [DeliveryCompanyController::class, 'connect']);
        Route::post('/{id}/disconnect', [DeliveryCompanyController::class, 'disconnect']);
        Route::post('/{id}/enable-updates', [DeliveryCompanyController::class, 'enableOrdersUpdates']);
        Route::post('/{id}/disable-updates', [DeliveryCompanyController::class, 'disableOrdersUpdates']);
        Route::get('/{id}/test-connection', [DeliveryCompanyController::class, 'testConnection']);
    });

    
    // Product management routes (full CRUD with authentication)
    Route::apiResource('products', ProductController::class);
    
    // Additional product routes
    Route::prefix('products')->group(function () {
        Route::post('/{id}/duplicate', [ProductController::class, 'duplicate']);
        Route::delete('/bulk/delete', [ProductController::class, 'bulkDestroy']);
        Route::put('/bulk/status', [ProductController::class, 'bulkUpdateStatus']);
        Route::get('/summary', [ProductController::class, 'summary']);
    });
    
    // Shop/Shopify integration routes
    Route::prefix('shop')->group(function () {
        Route::get('/test-shopify-connection', [ShopController::class, 'testShopifyConnection']);
        Route::get('/sync-shopify', [ShopController::class, 'syncShopify']);
        Route::post('/shopify-credentials', [ShopController::class, 'updateShopifyCredentials']);
    });

    //api/auth/team/members
    Route::get('/auth/team/members', [TeamController::class, 'index']);
    Route::post('/auth/team/members', [TeamController::class, 'storeMember']);
    Route::put('/auth/team/members/{id}', [TeamController::class, 'updateMember']);
    Route::delete('/auth/team/members/{id}', [TeamController::class, 'destroyMember']);
    Route::post('/auth/team/settings', [TeamController::class, 'updateSettings']);
    Route::post('/auth/team/impersonate/{id}', [TeamController::class, 'impersonate']);
});
Route::middleware('auth:sanctum')->post('/upload', [UploadController::class, 'store']);
