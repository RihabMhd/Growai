<?php

namespace App\Application\Orders\RecoverAbandonedOrder;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\OrderStateMachine;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RecoverAbandonedOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(int|string $orderId, ?int $actorId): Order
    {
        $order = $this->orders->findWithRelations($orderId);

        DB::transaction(function () use ($order, $actorId) {
            $locked = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'abandoned') {
                throw new UnprocessableEntityHttpException('Only abandoned orders can be recovered.');
            }

            (new OrderStateMachine($locked))->transitionTo('recovered');

            $locked->update([
                'status' => $locked->status,
                'is_abandoned' => false,
            ]);
        });

        return $this->orders->findWithRelations($orderId);
    }
}
