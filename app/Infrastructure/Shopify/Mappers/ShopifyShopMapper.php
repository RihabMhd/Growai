<?php

namespace App\Infrastructure\Shopify\Mappers;

use App\Domain\Shopify\DTOs\ShopifyShopDTO;

final class ShopifyShopMapper
{
    public function fromOAuth(
        string $domain,
        string $token
    ): ShopifyShopDTO {

        return new ShopifyShopDTO(
            domain: $domain,
            accessToken: $token,
            isActive: true,
            name: $domain
        );
    }
}