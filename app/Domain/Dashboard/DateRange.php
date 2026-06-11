<?php 
namespace App\Domain\Dashboard;

use Carbon\Carbon;

final class DateRange
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly Carbon $prevStart,
        public readonly Carbon $prevEnd,
    ) {}
}