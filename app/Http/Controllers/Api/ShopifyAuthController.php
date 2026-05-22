<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $shop  = config('services.shopify.shop');
        $state = Str::random(40);

        // Use cache instead of session (API routes are stateless)
        Cache::put('shopify_oauth_state_' . $state, true, now()->addMinutes(10));

        $query = http_build_query([
            'client_id'    => config('services.shopify.client_id'),
            'scope'        => 'read_products,write_products,read_orders,write_orders',
            'redirect_uri' => config('services.shopify.redirect_uri'),
            'state'        => $state,
        ]);

        return redirect("https://{$shop}/admin/oauth/authorize?{$query}");
    }

    public function callback(Request $request)
    {
        $state = $request->query('state');

        // Verify state exists in cache
        if (!$state || !Cache::pull('shopify_oauth_state_' . $state)) {
            abort(403, 'Invalid state.');
        }

        $shop = $request->query('shop');
        $code = $request->query('code');

        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id'     => config('services.shopify.client_id'),
            'client_secret' => config('services.shopify.client_secret'),
            'code'          => $code,
        ]);

        if (!$response->successful()) {
            return redirect(config('app.frontend_url') . '/integrations/shopify?error=1');
        }

        $accessToken = $response->json('access_token');

        Shop::updateOrCreate(
            ['shopify_domain' => $shop],
            [
                'name'         => $shop, 
                'access_token' => $accessToken,
                'is_active'    => true,
            ]
        );

        return redirect(config('app.frontend_url') . '/integrations/shopify?connected=1');
    }
}
