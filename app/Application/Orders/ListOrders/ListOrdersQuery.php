<?php

namespace App\Application\Orders\ListOrders;

use App\Domain\Teams\Models\User;


final class ListOrdersQuery
{
    public function __construct(
        public readonly User    $actor,
        public readonly ?string $search,
        public readonly ?string $type,
        public readonly ?string $status,
        public readonly ?bool $isAbandoned,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            actor:  $request->user(),
            search: $request->filled('search') ? $request->input('search') : null,
            type:   $request->filled('type')   ? $request->input('type')   : null,
            status: $request->filled('status') && $request->input('status') !== 'all'
                        ? $request->input('status')
                        : null,
            isAbandoned: $request->has('is_abandoned')
                        ? $request->boolean('is_abandoned')
                        : null,
        );
    }
}
