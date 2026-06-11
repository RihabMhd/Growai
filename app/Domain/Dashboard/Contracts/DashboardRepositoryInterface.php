<?php 
namespace App\Domain\Dashboard\Contracts;

use App\Domain\Dashboard\DateRange;
use App\Domain\Dashboard\DashboardVisibilityPolicy;

interface DashboardRepositoryInterface
{
    public function getOrderStats(DateRange $range, DashboardVisibilityPolicy $policy, ?int $shopId = null): array;
    public function getShops(): \Illuminate\Support\Collection;
    public function getProductCount(DashboardVisibilityPolicy $policy): int;
    public function getClientCount(DashboardVisibilityPolicy $policy): int;
    public function getTeamMemberCount(?int $teamId): int;
}