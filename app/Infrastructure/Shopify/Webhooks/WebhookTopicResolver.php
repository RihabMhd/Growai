<?php

namespace App\Infrastructure\Shopify\Webhooks;

use App\Domain\Shopify\Enums\ShopifyWebhookTopic;
use App\Domain\Shopify\Exceptions\UnsupportedWebhookTopicException;

final class WebhookTopicResolver
{
    public function resolve(
        string $topic
    ): ShopifyWebhookTopic {

        return ShopifyWebhookTopic::tryFrom($topic)
            ?? throw UnsupportedWebhookTopicException::topic($topic);
    }
}