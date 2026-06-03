<?php

namespace App\Infrastructure\Shopify\Webhooks;

use App\Domain\Shopify\DTOs\ShopifyWebhookPayloadDTO;
use App\Domain\Shopify\Enums\ShopifyWebhookTopic;
use App\Domain\Shopify\Exceptions\ShopNotFoundException;
use App\Infrastructure\Shopify\Repositories\EloquentShopRepository;

final readonly class ShopifyWebhookHandler
{
    public function __construct(
        private WebhookTopicResolver $resolver,
        private EloquentShopRepository $shops,
        private OrderWebhookHandler $orders,
        private ProductWebhookHandler $products,
    ) {}

    public function handle(
        ShopifyWebhookPayloadDTO $payload
    ): void {

        $shop = $this->shops->findByDomain(
            $payload->shopDomain
        );

        if (!$shop) {
            throw ShopNotFoundException::byDomain(
                $payload->shopDomain
            );
        }

        $topic = $this->resolver->resolve(
            $payload->topic
        );

        match ($topic) {

            ShopifyWebhookTopic::ORDER_CREATED =>
                $this->orders->handleCreated(
                    $shop,
                    $payload->payload
                ),

            ShopifyWebhookTopic::ORDER_UPDATED =>
                $this->orders->handleUpdated(
                    $shop,
                    $payload->payload
                ),

            ShopifyWebhookTopic::ORDER_CANCELLED =>
                $this->orders->handleCancelled(
                    $shop,
                    $payload->payload
                ),

            ShopifyWebhookTopic::ORDER_PAID =>
                $this->orders->handlePaid(
                    $shop,
                    $payload->payload
                ),

            ShopifyWebhookTopic::ORDER_FULFILLED =>
                $this->orders->handleFulfilled(
                    $shop,
                    $payload->payload
                ),

            ShopifyWebhookTopic::PRODUCT_UPDATED =>
                $this->products->handleUpdated(
                    $shop,
                    $payload->payload
                ),

            ShopifyWebhookTopic::PRODUCT_DELETED =>
                $this->products->handleDeleted(
                    $shop,
                    $payload->payload
                ),
        };
    }
}