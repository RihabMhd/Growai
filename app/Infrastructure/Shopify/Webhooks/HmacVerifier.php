<?php

namespace App\Infrastructure\Shopify\Webhooks;

final class HmacVerifier
{
    public function verify(
        string $payload,
        string $receivedHmac,
        string $secret
    ): bool {

        $calculated = base64_encode(
            hash_hmac(
                'sha256',
                $payload,
                $secret,
                true
            )
        );

        return hash_equals(
            $calculated,
            $receivedHmac
        );
    }
}