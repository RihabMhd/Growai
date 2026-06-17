<?php

namespace App\Application\Dashboard\GetDashboard;

final class GetDashboardQuery
{
    public function __construct(
        public readonly string $period,
        public readonly int $userId,
        public readonly ?int $teamId,
        public readonly bool $isAgent,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {}
}
