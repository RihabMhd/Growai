<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Application\Shopify\DisconnectShop\DisconnectShopCommand;
use App\Application\Shopify\DisconnectShop\DisconnectShopHandler;
use App\Application\Shopify\GetShopStatus\GetShopStatusHandler;
use App\Application\Shopify\GetShopStatus\GetShopStatusQuery;
use App\Application\Shopify\ListShopOrders\ListShopOrdersHandler;
use App\Application\Shopify\ListShopOrders\ListShopOrdersQuery;
use App\Application\Shopify\ListShopProducts\ListShopProductsHandler;
use App\Application\Shopify\ListShopProducts\ListShopProductsQuery;
use App\Application\Shopify\ListShops\ListShopsHandler;
use App\Application\Shopify\ListShops\ListShopsQuery;
use App\Application\Shopify\SyncShopProducts\SyncShopProductsCommand;
use App\Application\Shopify\SyncShopProducts\SyncShopProductsHandler;
use App\Application\Shopify\UpdateShop\UpdateShopCommand;
use App\Application\Shopify\UpdateShop\UpdateShopHandler;
use App\Domain\Shopify\Models\Shop;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShopifyController extends Controller
{
    public function status(
        Request $request,
        GetShopStatusHandler $handler
    ): JsonResponse {

        $result = $handler->handle(
            new GetShopStatusQuery(
                shopId: $request->query('shop_id'),
                user: $request->user()
            )
        );

        return response()->json($result);
    }

    public function syncProducts(
        Request $request,
        SyncShopProductsHandler $handler
    ): JsonResponse {

        $handler->handle(
            new SyncShopProductsCommand(
                shopId: (int) $request->query('shop_id')
            )
        );

        return response()->json([
            'message' => 'Sync queued successfully.'
        ]);
    }

    public function products(
        Request $request,
        ListShopProductsHandler $handler
    ): JsonResponse {

        return response()->json(
            $handler->handle(
                new ListShopProductsQuery(
                    shopId: (int) $request->query('shop_id'),
                    search: $request->query('search'),
                    perPage: (int) $request->query('per_page', 24)
                )
            )
        );
    }

    public function orders(
        Request $request,
        ListShopOrdersHandler $handler
    ): JsonResponse {

        return response()->json(
            $handler->handle(
                new ListShopOrdersQuery(
                    shopId: (int) $request->query('shop_id'),
                    status: $request->query('status'),
                    search: $request->query('search'),
                    perPage: (int) $request->query('per_page', 20)
                )
            )
        );
    }

    public function listShops(
        ListShopsHandler $handler
    ): JsonResponse {

        return response()->json([
            'shops' => $handler->handle(
                new ListShopsQuery()
            )
        ]);
    }

    public function updateShop(
        Request $request,
        Shop $shop,
        UpdateShopHandler $handler
    ): JsonResponse {

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'boutique_name' => ['nullable', 'string', 'max:120'],
        ]);

        $updated = $handler->handle(
            new UpdateShopCommand(
                shopId: $shop->id,
                name: $validated['name'] ?? null,
                boutiqueName: $validated['boutique_name'] ?? null,
            )
        );

        return response()->json([
            'shop' => $updated
        ]);
    }

    public function disconnectShop(
        Shop $shop,
        DisconnectShopHandler $handler
    ): JsonResponse {

        $handler->handle(
            new DisconnectShopCommand(
                $shop->id
            )
        );

        return response()->json([
            'message' => 'Store disconnected.'
        ]);
    }
}