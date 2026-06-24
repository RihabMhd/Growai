<?php


namespace App\Application\CarrierActions\Commands;

final class RegisterWebhookCommand
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $teamId,
    ) {}
}