<?php

namespace App\Http\Controllers\Api\Order;

use App\Application\Orders\AssignOrder\AssignOrderCommand;
use App\Application\Orders\AssignOrder\AssignOrderHandler;
use App\Application\Orders\BulkAssignOrders\BulkAssignOrdersCommand;
use App\Application\Orders\BulkAssignOrders\BulkAssignOrdersHandler;
use App\Application\Orders\BulkUpdateOrderStatus\BulkUpdateOrderStatusCommand;
use App\Application\Orders\BulkUpdateOrderStatus\BulkUpdateOrderStatusHandler;
use App\Application\Orders\CreateOrder\CreateOrderCommand;
use App\Application\Orders\CreateOrder\CreateOrderHandler;
use App\Application\Orders\GetOrder\GetOrderHandler;
use App\Application\Orders\ListOrders\ListOrdersQuery;
use App\Application\Orders\ListOrders\ListOrdersHandler;
use App\Application\Orders\SyncAbandonedOrders\SyncAbandonedOrdersHandler;
use App\Application\Orders\UpdateOrder\UpdateOrderCommand;
use App\Application\Orders\UpdateOrder\UpdateOrderHandler;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OrderController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly ListOrdersHandler            $listOrders,
        private readonly CreateOrderHandler           $createOrder,
        private readonly GetOrderHandler              $getOrder,
        private readonly UpdateOrderHandler           $updateOrder,
        private readonly AssignOrderHandler           $assignOrder,
        private readonly BulkAssignOrdersHandler      $bulkAssignOrders,
        private readonly BulkUpdateOrderStatusHandler $bulkUpdateStatus,
        private readonly SyncAbandonedOrdersHandler   $syncAbandoned,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET /orders
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $result = $this->listOrders->handle(
            ListOrdersQuery::fromRequest($request)
        );

        return response()->json([
            'orders'        => OrderResource::collection($result['orders']),
            'metrics'       => $result['metrics'],
            'active_agents' => $result['active_agents'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /orders
    // ─────────────────────────────────────────────────────────────────────────

    // OrderController.php - store() method
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:30',
            'customer_email' => 'nullable|email',
            'province'       => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'street'         => 'nullable|string|max:255',
            'source'         => 'nullable|string|max:50',
            'notes'          => 'nullable|string',
            'is_abandoned'   => 'nullable|boolean',
            'shipping_price' => 'nullable|numeric|min:0',
            // Remove 'status' from validation - it should not be settable
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Explicitly set status to 'nouveau' (new)
        $validated['status'] = 'nouveau';

        $order = $this->createOrder->handle(
            CreateOrderCommand::fromArray($validated, $request->user()->id)
        );

        return response()->json(new OrderResource($order), 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /orders/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int|string $id): JsonResponse
    {
        $order = $this->getOrder->handle($id);

        return response()->json(
            new OrderResource(
                $order->load([
                    'histories.user:id,name',
                    'assignedAgent:id,name',
                ])
            )
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /orders/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'status'           => 'nullable|string',
            'financial_status' => 'nullable|string',
            'notes'            => 'nullable|string',
            'shipping_price'   => 'nullable|numeric|min:0',
            'customer_name'    => 'nullable|string|max:255',
            'customer_phone'   => 'nullable|string|max:30',
            'customer_email'   => 'nullable|email',
            'province'         => 'nullable|string|max:100',
            'city'             => 'nullable|string|max:100',
            'street'           => 'nullable|string|max:255',
            'items'            => 'nullable|array|min:1',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity'   => 'required_with:items|integer|min:1',
        ]);

        $order = $this->updateOrder->handle(
            UpdateOrderCommand::fromArray($id, $validated, $request->user()->id)
        );

        return response()->json(new OrderResource($order));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /orders/{id}/assign
    // ─────────────────────────────────────────────────────────────────────────

    public function assign(Request $request, int|string $id): JsonResponse
    {
        $this->authorize('assign', \App\Domain\Orders\Models\Order::class);

        $validated = $request->validate([
            'agent_id' => 'nullable|integer|exists:users,id',
        ]);

        $order = $this->assignOrder->handle(new AssignOrderCommand(
            orderId: $id,
            agentId: $validated['agent_id'] ?? null,
            actorId: $request->user()->id,
        ));

        return response()->json(new OrderResource($order));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /orders/bulk-assign
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkAssign(Request $request): JsonResponse
    {
        $this->authorize('assign', \App\Domain\Orders\Models\Order::class);

        $validated = $request->validate([
            'order_ids'   => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'agent_id'    => 'nullable|integer|exists:users,id',
        ]);

        $count = $this->bulkAssignOrders->handle(new BulkAssignOrdersCommand(
            orderIds: $validated['order_ids'],
            agentId: $validated['agent_id'] ?? null,
            actorId: $request->user()->id,
        ));

        return response()->json(['updated' => $count]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /orders/bulk-status
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('assign', \App\Domain\Orders\Models\Order::class);

        $validated = $request->validate([
            'order_ids'   => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'status'      => 'required|string',
        ]);

        $count = $this->bulkUpdateStatus->handle(new BulkUpdateOrderStatusCommand(
            orderIds: $validated['order_ids'],
            newStatus: $validated['status'],
            actorId: $request->user()->id,
        ));

        return response()->json(['updated' => $count]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /orders/sync-abandoned
    // ─────────────────────────────────────────────────────────────────────────

    public function syncAbandoned(): JsonResponse
    {
        $result = $this->syncAbandoned->handle();

        return response()->json($result);
    }
}
