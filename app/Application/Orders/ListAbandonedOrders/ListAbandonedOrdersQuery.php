<?php

namespace App\Application\Orders\ListAbandonedOrders;

use App\Domain\Teams\Models\User;
use Illuminate\Http\Request;

final class ListAbandonedOrdersQuery
{
    public function __construct(
        public readonly User $actor,
        public readonly string $period,
        public readonly ?string $status,
        public readonly bool $hasPhone,
        public readonly ?string $search,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            actor: $request->user(),
            period: $request->input('period', '30d'),
            status: $request->filled('status') && $request->input('status') !== 'all'
                ? $request->input('status')
                : null,
            hasPhone: $request->boolean('has_phone'),
            search: $request->filled('search') ? $request->input('search') : null,
            page: max(1, (int) $request->input('page', 1)),
            perPage: min(100, max(10, (int) $request->input('per_page', 15))),
        );
    }
}
