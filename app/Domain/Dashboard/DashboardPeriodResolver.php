<?php 
namespace App\Domain\Dashboard;

use Carbon\Carbon;

final class DashboardPeriodResolver
{
    public function resolve(string $period): DateRange
    {
        $now = Carbon::now();

        [$start, $end] = match ($period) {
            'yesterday'   => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'this_month'  => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            default       => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };

        $lengthSeconds = $start->diffInSeconds($end);
        $prevEnd       = $start->copy()->subSecond();
        $prevStart     = $prevEnd->copy()->subSeconds($lengthSeconds);

        return new DateRange($start, $end, $prevStart, $prevEnd);
    }
}