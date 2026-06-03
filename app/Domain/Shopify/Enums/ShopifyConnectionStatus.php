<?php

namespace App\Domain\Shopify\Enums;

enum ShopifyConnectionStatus: string
{
    case CONNECTED = 'connected';

    case DISCONNECTED = 'disconnected';

    case INVALID_TOKEN = 'invalid_token';

    case NOT_FOUND = 'not_found';
}