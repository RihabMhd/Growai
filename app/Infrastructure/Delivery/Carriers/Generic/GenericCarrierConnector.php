<?php

namespace App\Infrastructure\Delivery\Carriers\Generic;

final class GenericCarrierConnector extends AbstractCarrierConnector
{
    public function validateWebhook(array $payload): bool
    {
        return parent::validateWebhook($payload) && isset($payload['status']);
    }
}
