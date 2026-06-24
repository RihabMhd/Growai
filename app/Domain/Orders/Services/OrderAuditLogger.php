<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;


interface OrderAuditLogger
{

    public function log(
        Order   $order,
        ?int    $userId,
        string  $actionType,
        ?string $oldValue,
        ?string $newValue,
        string  $description,
    ): void;
}