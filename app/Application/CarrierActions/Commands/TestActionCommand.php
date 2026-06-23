<?php
// app/Application/CarrierActions/Commands/TestActionCommand.php

namespace App\Application\CarrierActions\Commands;

final class TestActionCommand
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $actionKey,
        public readonly int $teamId,
    ) {}
}