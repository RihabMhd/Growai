<?php

namespace App\Domain\Dashboard;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class DashboardPeriodResolver
{
    public function resolve(
        string $period,
        ?string $from = null,
        ?string $to = null,
    ): DateRange {
        $now = Carbon::now();

        [$start, $end] = match ($period) {

            'all_time' => [
                Carbon::create(2000, 1, 1)->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],

            'last_7_days' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'last_30_days' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfDay(),
            ],

            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],

            'last_90_days' => [
                $now->copy()->subDays(89)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'custom' => [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ],

            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };

        $lengthSeconds = $start->diffInSeconds($end);
        $prevEnd       = $start->copy()->subSecond();
        $prevStart     = $prevEnd->copy()->subSeconds($lengthSeconds);


        Log::debug('Dashboard range', [
            'period' => $period,
            'start' => $start,
            'end' => $end,
        ]);

        return new DateRange($start, $end, $prevStart, $prevEnd);
    }
}
