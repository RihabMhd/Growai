<?php

namespace App\Application\Dashboard\GetDashboard;

use App\Domain\Dashboard\AbandonedOrderAnalyticsService;
use App\Domain\Dashboard\DashboardPeriodResolver;
use App\Domain\Dashboard\DashboardVisibilityPolicy;
use App\Domain\Dashboard\Contracts\DashboardRepositoryInterface;

final class GetDashboardHandler
{
    public function __construct(
        private readonly DashboardPeriodResolver      $periodResolver,
        private readonly DashboardRepositoryInterface $repository,
        private readonly AbandonedOrderAnalyticsService $abandonedAnalytics,
    ) {}

    public function handle(GetDashboardQuery $query): array
    {

        $range = $this->periodResolver->resolve(
            $query->period,
            $query->from,
            $query->to,
        );
        $policy = new DashboardVisibilityPolicy($query->userId, $query->isAgent);

        $global = $this->repository->getOrderStats($range, $policy, shopId: null, teamId: $query->teamId);
        $global['products']     = $this->repository->getProductCount($policy);
        $global['clients']      = $this->repository->getClientCount($policy);
        $global['team_members'] = $this->repository->getTeamMemberCount($query->teamId);
        $global['abandoned_analytics'] = $this->abandonedAnalytics->calculate(
            $range,
            $policy,
            shopId: null,
            teamId: $query->teamId,
        );

        $shops = $this->repository->getShops($query->teamId)->map(fn($shop) => array_merge(
            [
                'id'        => $shop->id,
                'name'      => $shop->boutique_name ?? $shop->name,
                'platform'  => $shop->platform,
                'domain'    => $shop->shopify_domain,
                'is_active' => $shop->is_active,
            ],
            $this->repository->getOrderStats($range, $policy, $shop->id, $query->teamId),
            [
                'abandoned_analytics' => $this->abandonedAnalytics->calculate(
                    $range,
                    $policy,
                    shopId: $shop->id,
                    teamId: $query->teamId,
                ),
            ],
        ));
        return [
            'period' => $query->period,
            'global' => $global,
            'shops'  => $shops,
        ];
    }
}
