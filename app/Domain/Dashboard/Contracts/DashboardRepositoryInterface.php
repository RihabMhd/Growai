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

    /**
     * @param int[] $shopIds
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function getShopsStats(
        DateRange                 $range,
        DashboardVisibilityPolicy $policy,
        array                     $shopIds,
        ?int                      $teamId = null,
    ): \Illuminate\Support\Collection;

    public function getShops(?int $teamId = null): Collection;

    public function getProductCount(DashboardVisibilityPolicy $policy, ?int $teamId = null): int;

    public function getClientCount(DashboardVisibilityPolicy $policy, ?int $teamId = null): int;

    public function getTeamMemberCount(?int $teamId): int;
}
