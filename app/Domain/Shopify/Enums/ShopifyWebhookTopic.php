<?php

namespace App\Domain\Shopify\Enums;

enum ShopifyWebhookTopic: string
{
    case ORDER_CREATED = 'orders/create';

    case ORDER_UPDATED = 'orders/updated';

    case ORDER_CANCELLED = 'orders/cancelled';

    case ORDER_PAID = 'orders/paid';

    case ORDER_FULFILLED = 'orders/fulfilled';

    case PRODUCT_UPDATED = 'products/update';

    case PRODUCT_DELETED = 'products/delete';
}