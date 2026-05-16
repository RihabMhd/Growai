<?php 

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\TeamController;

Route::prefix('auth')->group(function () {

    // Email login
    Route::post('/login', [AuthController::class, 'login']);

    // Password 
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

    // Google
    Route::get('/google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [SocialAuthController::class, 'googleCallback']);

    // Facebook
    Route::get('/facebook/redirect', [SocialAuthController::class, 'facebookRedirect']);
    Route::get('/facebook/callback', [SocialAuthController::class, 'facebookCallback']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        
        // Team Routes
        Route::get('/team', [TeamController::class, 'index']);
        Route::post('/team/members', [TeamController::class, 'storeMember']);
        Route::put('/team/members/{id}', [TeamController::class, 'updateMember']);
        Route::post('/team/settings', [TeamController::class, 'updateSettings']);
        Route::delete('/team/members/{id}', [TeamController::class, 'destroyMember']);
        Route::post('/team/impersonate/{id}', [TeamController::class, 'impersonate']);
    });
});