<?php

namespace App\Application\Dashboard\GetDashboard;

use App\Domain\Dashboard\AbandonedOrderAnalyticsService;
use App\Domain\Dashboard\DashboardPeriodResolver;
use App\Domain\Dashboard\DashboardVisibilityPolicy;
use App\Domain\Dashboard\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final class GetDashboardHandler
{
    public function __construct(
        private readonly DashboardPeriodResolver      $periodResolver,
        private readonly DashboardRepositoryInterface $repository,
        private readonly AbandonedOrderAnalyticsService $abandonedAnalytics,
    ) {}

    public function handle(GetDashboardQuery $query): array
    {
        $cacheKey = sprintf(
            'dashboard|%s|u%d|t%s|f%s|t%s',
            $query->period,
            $query->userId,
            $query->teamId ?? '0',
            $query->from ?? '',
            $query->to ?? '',
        );

        return Cache::remember($cacheKey, 60, function () use ($query) {

            $range = $this->periodResolver->resolve(
                $query->period,
                $query->from,
                $query->to,
            );
            $policy = new DashboardVisibilityPolicy($query->userId, $query->isAgent);

            $global = $this->repository->getOrderStats($range, $policy, shopId: null, teamId: $query->teamId);
            $global['products']     = $this->repository->getProductCount($policy, $query->teamId);
            $global['clients']      = $this->repository->getClientCount($policy, $query->teamId);
            $global['team_members'] = $this->repository->getTeamMemberCount($query->teamId);
            $global['abandoned_analytics'] = $this->abandonedAnalytics->calculate(
                $range,
                $policy,
                shopId: null,
                teamId: $query->teamId,
            );

            $shops = $this->repository->getShops($query->teamId);
            $shopIds = $shops->pluck('id')->toArray();

            if (!empty($shopIds)) {
                $shopsStats = $this->repository->getShopsStats($range, $policy, $shopIds, $query->teamId);
                $shops = $shops->map(fn($shop) => array_merge(
                    [
                        'id'        => $shop->id,
                        'name'      => $shop->boutique_name ?? $shop->name,
                        'platform'  => $shop->platform,
                        'domain'    => $shop->shopify_domain,
                        'is_active' => $shop->is_active,
                    ],
                    $shopsStats->get($shop->id, []),
                ));
            }

            return [
                'period' => $query->period,
                'global' => $global,
                'shops'  => $shops,
            ];
        });
    }
}
