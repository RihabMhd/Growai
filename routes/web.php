<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-shopify-products', function () {

    $shop = \App\Domain\Shopify\Models\Shop::find(4);

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'X-Shopify-Access-Token' => $shop->access_token,
    ])->get(
        "https://{$shop->shopify_domain}/admin/api/2025-01/products.json"
    );

    return response()->json([
        'status' => $response->status(),
        'body' => json_decode($response->body(), true),
    ]);
});