<?php

use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ShopifyWebhookController;
use App\Http\Controllers\Api\ShopifyController;
use App\Http\Controllers\Api\ShopifyAuthController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

    Route::get('/google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [SocialAuthController::class, 'googleCallback']);

    Route::get('/facebook/redirect', [SocialAuthController::class, 'facebookRedirect']);
    Route::get('/facebook/callback', [SocialAuthController::class, 'facebookCallback']);

    // Shopify OAuth (must be public — browser redirects, no Bearer token)
    Route::get('/shopify/redirect', [ShopifyAuthController::class, 'redirect']);
    Route::get('/shopify/callback', [ShopifyAuthController::class, 'callback']);
});

/*
|--------------------------------------------------------------------------
| Public Product Routes
|--------------------------------------------------------------------------
*/

Route::get('/products/public', [ProductController::class, 'index']);
Route::get('/products/public/{product}', [ProductController::class, 'show']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/tag/{tag}', [ProductController::class, 'getByTag']);
Route::get('/products-summary', [ProductController::class, 'summary']);

/*
|--------------------------------------------------------------------------
| Webhooks (public — signed by HMAC, no Bearer token)
|--------------------------------------------------------------------------
*/

Route::post('/shipments/webhook/{companyId}', [ShipmentController::class, 'handleWebhook']);

Route::post('/webhooks/shopify/{shopDomain}', [ShopifyWebhookController::class, 'handle'])
    ->name('shopify.webhook');

/*
|--------------------------------------------------------------------------
| Shopify Integration (public — called on page load before auth check)
|--------------------------------------------------------------------------
*/

Route::prefix('shopify')->group(function () {
    Route::get('/status', [ShopifyController::class, 'status']);
    Route::post('/sync-products', [ShopifyController::class, 'syncProducts']);
    Route::get('/products', [ShopifyController::class, 'products']);
    Route::get('/orders', [ShopifyController::class, 'orders']);
});

Route::get   ('/shopify/shops',          [ShopifyController::class, 'listShops']);
Route::patch ('/shopify/shops/{shop}',   [ShopifyController::class, 'updateShop']);
Route::delete('/shopify/shops/{shop}',   [ShopifyController::class, 'disconnectShop']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    /*
    |------------------------------------------------------------------
    | Authentication
    |------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
        Route::post('/2fa/toggle', [AuthController::class, 'toggle2FA']);
    });

    /*
    |------------------------------------------------------------------
    | Team Management
    |------------------------------------------------------------------
    */

    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::post('/members', [TeamController::class, 'storeMember']);
        Route::put('/members/{id}', [TeamController::class, 'updateMember']);
        Route::delete('/members/{id}', [TeamController::class, 'destroyMember']);
        Route::get('/settings', [TeamController::class, 'settings']);
        Route::post('/settings', [TeamController::class, 'updateSettings']);
        Route::post('/impersonate/{id}', [TeamController::class, 'impersonate']);
    });

    /*
    |------------------------------------------------------------------
    | Orders
    |------------------------------------------------------------------
    */

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::post('/sync-abandoned', [OrderController::class, 'syncAbandoned']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::post('/{id}/assign', [OrderController::class, 'assign']);
    });

    /*
    |------------------------------------------------------------------
    | Shipments
    |------------------------------------------------------------------
    */

    Route::prefix('shipments')->group(function () {
        Route::get('/', [ShipmentController::class, 'index']);
        Route::post('/', [ShipmentController::class, 'store']);
        Route::get('/{id}', [ShipmentController::class, 'show']);
        Route::put('/{id}', [ShipmentController::class, 'update']);
        Route::delete('/{id}', [ShipmentController::class, 'destroy']);
        Route::get('/{id}/tracking', [ShipmentController::class, 'getTracking']);
    });

    /*
    |------------------------------------------------------------------
    | Delivery Companies
    |------------------------------------------------------------------
    */

    Route::prefix('companies')->group(function () {
        Route::get('/', [DeliveryCompanyController::class, 'index']);
        Route::get('/{id}', [DeliveryCompanyController::class, 'show']);
        Route::post('/{id}/connect', [DeliveryCompanyController::class, 'connect']);
        Route::post('/{id}/disconnect', [DeliveryCompanyController::class, 'disconnect']);
        Route::post('/{id}/enable-updates', [DeliveryCompanyController::class, 'enableOrdersUpdates']);
        Route::post('/{id}/disable-updates', [DeliveryCompanyController::class, 'disableOrdersUpdates']);
        Route::get('/{id}/test-connection', [DeliveryCompanyController::class, 'testConnection']);
    });

    /*
    |------------------------------------------------------------------
    | Products
    |------------------------------------------------------------------
    */

    Route::apiResource('products', ProductController::class);

    Route::prefix('products')->group(function () {
        Route::post('/{id}/duplicate', [ProductController::class, 'duplicate']);
        Route::delete('/bulk/delete', [ProductController::class, 'bulkDestroy']);
        Route::put('/bulk/status', [ProductController::class, 'bulkUpdateStatus']);
        Route::get('/summary', [ProductController::class, 'summary']);
    });

    /*
    |------------------------------------------------------------------
    | Shop Settings
    |------------------------------------------------------------------
    */

    Route::prefix('shop')->group(function () {
        Route::get('/test-shopify-connection', [ShopController::class, 'testShopifyConnection']);
        Route::get('/sync-shopify', [ShopController::class, 'syncShopify']);
        Route::post('/shopify-credentials', [ShopController::class, 'updateShopifyCredentials']);
    });

    /*
    |------------------------------------------------------------------
    | Order Statuses
    |------------------------------------------------------------------
    */

    Route::get('/order-statuses', [OrderStatusController::class, 'index']);
    Route::post('/order-statuses/{id}/auto-send', [OrderStatusController::class, 'toggleAutoSend']);
    Route::post('/order-statuses/{id}/save-template', [OrderStatusController::class, 'saveTemplate']);
    Route::post('/company-statuses/{id}/auto-send', [OrderStatusController::class, 'toggleAutoSend']);
    Route::post('/company-statuses/{id}/save-template', [OrderStatusController::class, 'saveTemplate']);

    /*
    |------------------------------------------------------------------
    | Uploads
    |------------------------------------------------------------------
    */

    Route::post('/upload', [UploadController::class, 'store']);
});