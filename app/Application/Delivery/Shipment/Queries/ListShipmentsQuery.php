<?php

namespace App\Application\Delivery\Shipment\Queries;

final readonly class ListShipmentsQuery
{
    public function __construct(
        public ?int $orderId = null,
        public ?string $statusSlug = null,
        public ?int $deliveryCompanyId = null,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            orderId: $request->filled('order_id') ? (int) $request->input('order_id') : null,
            statusSlug: $request->input('status'),
            deliveryCompanyId: $request->filled('delivery_company_id')
                ? (int) $request->input('delivery_company_id')
                : null,
        );
    }
}
