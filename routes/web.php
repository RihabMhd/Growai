<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-shopify', function () {
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN')
    ])->get("https://".env('SHOPIFY_SHOP')."/admin/api/2026-01/products.json");

    return $response->json();
});