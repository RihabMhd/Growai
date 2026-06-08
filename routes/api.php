<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\SocialAuthController;

use App\Http\Controllers\Api\Order\OrderController;

use App\Http\Controllers\Api\Shopify\ShopifyAuthController;
use App\Http\Controllers\Api\Shopify\ShopifyController;
use App\Http\Controllers\Api\Shopify\ShopifyWebhookController;

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryCompanyController;
use App\Http\Controllers\Api\OrderStatusController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UploadController;

use App\Http\Controllers\Api\Products\ProductController;
use App\Http\Controllers\Api\Shops\ShopSessionController;

/*
|--------------------------------------------------------------------------
| Public — Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password',  [PasswordController::class, 'resetPassword']);

    Route::get('/google/redirect',   [SocialAuthController::class, 'googleRedirect']);
    Route::get('/google/callback',   [SocialAuthController::class, 'googleCallback']);
    Route::get('/facebook/redirect', [SocialAuthController::class, 'facebookRedirect']);
    Route::get('/facebook/callback', [SocialAuthController::class, 'facebookCallback']);

    Route::get('/shopify/redirect', [ShopifyAuthController::class, 'redirect']);
    Route::get('/shopify/callback', [ShopifyAuthController::class, 'callback']);
});

/*
|--------------------------------------------------------------------------
| Public — Webhooks
|--------------------------------------------------------------------------
*/

Route::post(
    '/shipments/webhook/{companyId}',
    [ShipmentController::class, 'handleWebhook']
);

Route::post(
    '/webhooks/shopify/{shopDomain}',
    [ShopifyWebhookController::class, 'handle']
)->name('shopify.webhook');

Route::post(
    'shopify/webhook/{shopDomain}',
    [ShopifyWebhookController::class, 'handle']
)->where('shopDomain', '[a-z0-9\-]+\.myshopify\.com');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {
        Route::get('/me',            [AuthController::class, 'me']);
        Route::post('/logout',       [AuthController::class, 'logout']);
        Route::put('/profile',       [AuthController::class, 'updateProfile']);
        Route::put('/password',      [AuthController::class, 'updatePassword']);
        Route::post('/2fa/toggle',   [AuthController::class, 'toggle2FA']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Shop Session (active shop switcher)
    |--------------------------------------------------------------------------
    */

    Route::prefix('session/shop')->group(function () {
        Route::get('/',  [ShopSessionController::class, 'show']);
        Route::put('/',  [ShopSessionController::class, 'update']);
        Route::delete('/',  [ShopSessionController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Shopify
    |--------------------------------------------------------------------------
    */

    Route::prefix('shopify')->group(function () {

        Route::get('/status',        [ShopifyController::class, 'status']);
        Route::post('/sync-products', [ShopifyController::class, 'syncProducts']);
        Route::get('/products',      [ShopifyController::class, 'products']);
        Route::get('/orders',        [ShopifyController::class, 'orders']);

        Route::prefix('shops')->group(function () {
            Route::get('/',                      [ShopifyController::class, 'listShops']);
            Route::post('/{shop}/sync-products',  [ShopifyController::class, 'syncProducts']);
            Route::patch('/{shop}',                [ShopifyController::class, 'updateShop']);
            Route::delete('/{shop}',                [ShopifyController::class, 'disconnectShop']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Products  —  scoped to /shops/{shop}/products
    |
    | Route order matters:
    |   1. Named sub-routes (summary, bulk-*) registered before the wildcard
    |      {product} segment to avoid shadowing.
    |   2. Public read routes are outside auth middleware intentionally;
    |      they have been removed from this block — see note below.
    |--------------------------------------------------------------------------
    */

    Route::prefix('shops/{shop}')->group(function () {

        // Aggregates & bulk — must precede apiResource wildcard
        Route::get('products/summary',      [ProductController::class, 'summary']);
        Route::post('products/bulk-delete',  [ProductController::class, 'bulkDestroy']);
        Route::post('products/bulk-status',  [ProductController::class, 'bulkUpdateStatus']);
        Route::post('sync-orders',[ShopifyController::class, 'syncOrders']);
        // Standard CRUD
        Route::apiResource('products', ProductController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    Route::prefix('orders')->group(function () {
        Route::get('/',               [OrderController::class, 'index']);
        Route::post('/',               [OrderController::class, 'store']);
        Route::post('/sync-abandoned', [OrderController::class, 'syncAbandoned']);
        Route::put('/bulk/status',    [OrderController::class, 'bulkUpdateStatus']);
        Route::put('/bulk/assign',    [OrderController::class, 'bulkAssign']);
        Route::get('/{id}',           [OrderController::class, 'show']);
        Route::put('/{id}',           [OrderController::class, 'update']);
        Route::post('/{id}/assign',    [OrderController::class, 'assign']);
    });

    /*
    |--------------------------------------------------------------------------
    | Team
    |--------------------------------------------------------------------------
    */

    Route::prefix('team')->group(function () {
        Route::get('/',                    [TeamController::class, 'index']);
        Route::post('/members',             [TeamController::class, 'storeMember']);
        Route::put('/members/{id}',        [TeamController::class, 'updateMember']);
        Route::delete('/members/{id}',        [TeamController::class, 'destroyMember']);
        Route::get('/settings',            [TeamController::class, 'settings']);
        Route::post('/settings',            [TeamController::class, 'updateSettings']);
        Route::post('/impersonate/{id}',    [TeamController::class, 'impersonate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Shipments
    |--------------------------------------------------------------------------
    */

    Route::prefix('shipments')->group(function () {
        Route::get('/',              [ShipmentController::class, 'index']);
        Route::post('/',              [ShipmentController::class, 'store']);
        Route::get('/{id}',          [ShipmentController::class, 'show']);
        Route::put('/{id}',          [ShipmentController::class, 'update']);
        Route::delete('/{id}',          [ShipmentController::class, 'destroy']);
        Route::get('/{id}/tracking', [ShipmentController::class, 'getTracking']);
    });

    /*
    |--------------------------------------------------------------------------
    | Delivery Companies
    |--------------------------------------------------------------------------
    */

    Route::prefix('companies')->group(function () {
        Route::get('/',                     [DeliveryCompanyController::class, 'index']);
        Route::get('/{id}',                 [DeliveryCompanyController::class, 'show']);
        Route::post('/{id}/connect',         [DeliveryCompanyController::class, 'connect']);
        Route::post('/{id}/disconnect',      [DeliveryCompanyController::class, 'disconnect']);
        Route::post('/{id}/enable-updates',  [DeliveryCompanyController::class, 'enableOrdersUpdates']);
        Route::post('/{id}/disable-updates', [DeliveryCompanyController::class, 'disableOrdersUpdates']);
        Route::get('/{id}/test-connection', [DeliveryCompanyController::class, 'testConnection']);
    });

    /*
    |--------------------------------------------------------------------------
    | Order Statuses
    |--------------------------------------------------------------------------
    */

    Route::prefix('order-statuses')->group(function () {
        Route::get('/',                    [OrderStatusController::class, 'index']);
        Route::post('/{id}/auto-send',      [OrderStatusController::class, 'toggleAutoSend']);
        Route::post('/{id}/save-template',  [OrderStatusController::class, 'saveTemplate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Company Statuses
    |--------------------------------------------------------------------------
    */

    Route::prefix('company-statuses')->group(function () {
        Route::post('/{id}/auto-send',     [OrderStatusController::class, 'toggleAutoSend']);
        Route::post('/{id}/save-template', [OrderStatusController::class, 'saveTemplate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */

    Route::prefix('clients')->group(function () {
        Route::get('/',      [ClientController::class, 'index']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */

    Route::post('/upload', [UploadController::class, 'store']);
});
