<?php

namespace App\Domain\Dashboard\Contracts;

use App\Domain\Dashboard\DateRange;
use App\Domain\Dashboard\DashboardVisibilityPolicy;
use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    public function getOrderStats(
        DateRange                 $range,
        DashboardVisibilityPolicy $policy,
        ?int                      $shopId = null,
        ?int                      $teamId = null,
    ): array;

    public function getShops(?int $teamId = null): Collection;

    public function getProductCount(DashboardVisibilityPolicy $policy): int;

    public function getClientCount(DashboardVisibilityPolicy $policy): int;

    public function getTeamMemberCount(?int $teamId): int;
}
