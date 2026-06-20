<?php

namespace App\Http\Controllers\Api\Order;

use App\Application\Orders\ListAbandonedOrders\ListAbandonedOrdersHandler;
use App\Application\Orders\ListAbandonedOrders\ListAbandonedOrdersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AbandonedOrderController extends Controller
{
    public function __construct(
        private readonly ListAbandonedOrdersHandler $handler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', 'string', 'in:24h,7d,30d,all'],
            'status' => ['nullable', 'string', 'in:all,open,recovered,recovery_sent'],
            'has_phone' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        return response()->json(
            $this->handler->handle(ListAbandonedOrdersQuery::fromRequest($request))
        );
    }
}
