<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Domain\Shopify\DTOs\ShopifyWebhookPayloadDTO;
use App\Domain\Shopify\Models\Shop;
use App\Http\Controllers\Controller;
use App\Infrastructure\Shopify\Jobs\ProcessWebhookJob;
use App\Infrastructure\Shopify\Webhooks\HmacVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ShopifyWebhookController extends Controller
{
    public function handle(
        Request $request,
        string $shopDomain,
        HmacVerifier $verifier
    ): Response {

        $shop = Shop::where(
            'shopify_domain',
            $shopDomain
        )
        ->where(
            'is_active',
            true
        )
        ->first();

        if (!$shop) {
            return response(
                'Shop not found',
                404
            );
        }

        $valid = $verifier->verify(
            payload: $request->getContent(),
            receivedHmac: $request->header(
                'X-Shopify-Hmac-Sha256',
                ''
            ),
            secret: $shop->webhook_secret
                ?? config(
                    'services.shopify.webhook_secret'
                )
        );

        if (!$valid) {
            return response(
                'Unauthorized',
                401
            );
        }

        ProcessWebhookJob::dispatch(
            new ShopifyWebhookPayloadDTO(
                topic: $request->header(
                    'X-Shopify-Topic'
                ),
                shopDomain: $shopDomain,
                payload: $request->json()->all()
            )
        );

        return response(
            'OK',
            200
        );
    }
}