<?php

namespace App\Domain\Dashboard;

use App\Domain\Orders\Actions\OrderMetricsCalculator;

final class DashboardStatus
{
    public const PENDING_STATUSES = ['new', 'pending', 'recovered'];
    public const DELIVERED_STATUS = 'delivered';
    public const RECOVERED_STATUS = 'recovered';

    public const REVENUE_STATUSES = ['confirmed', 'delivered'];

    public static function confirmedStatuses(): array
    {
        return OrderMetricsCalculator::CONFIRMED_STATUSES;
    }

    public static function cancelledStatuses(): array
    {
        return OrderMetricsCalculator::CANCELLED_STATUSES;
    }

    public static function pendingStatuses(): array
    {
        return self::PENDING_STATUSES;
    }

    public static function revenueStatuses(): array
    {
        return self::REVENUE_STATUSES;
    }
}
