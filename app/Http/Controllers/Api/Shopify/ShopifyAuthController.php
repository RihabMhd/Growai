<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Application\Shopify\ConnectShop\ConnectShopCommand;
use App\Application\Shopify\ConnectShop\ConnectShopHandler;
use App\Domain\Shopify\Exceptions\ShopifyOAuthException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class ShopifyAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $shop = $request->query('shop')
            ?? config('services.shopify.shop');

        $state = Str::random(40);

        Cache::put(
            'shopify_oauth_state_'.$state,
            true,
            now()->addMinutes(10)
        );

        $query = http_build_query([
            'client_id' => config('services.shopify.client_id'),
            'scope' => 'read_products,write_products,read_orders,write_orders',
            'redirect_uri' => config('services.shopify.redirect_uri'),
            'state' => $state,
        ]);

        return redirect(
            "https://{$shop}/admin/oauth/authorize?{$query}"
        );
    }

    public function callback(
        Request $request,
        ConnectShopHandler $handler
    ) {

        $state = $request->query('state');

        if (
            !$state ||
            !Cache::pull(
                'shopify_oauth_state_'.$state
            )
        ) {
            abort(403, 'Invalid state');
        }

        try {

            $handler->handle(
                new ConnectShopCommand(
                    shop: $request->query('shop'),
                    code: $request->query('code')
                )
            );

            return redirect(
                config('app.frontend_url')
                . '/integrations/shopify?connected=1'
            );

        } catch (ShopifyOAuthException) {

            return redirect(
                config('app.frontend_url')
                . '/integrations/shopify?error=1'
            );
        }
    }
}