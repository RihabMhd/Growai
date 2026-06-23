<?php
// app/Application/CarrierActions/Commands/SaveActionConfigCommand.php

namespace App\Application\CarrierActions\Commands;

final class SaveActionConfigCommand
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $actionKey,
        public readonly int $teamId,
        public readonly array $payload, // prefilled, hidden, credentials, auto_create_enabled
    ) {}
}