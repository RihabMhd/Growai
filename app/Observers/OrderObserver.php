<?php

namespace App\Observers;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Models\Order;

// thin observer, only responsible for firing domain events
class OrderObserver
{
    // auto-dispatch is triggered explicitly in createorderhandler
    public function created(Order $order): void
    {
    }

    // fire orderstatuschanged so all listeners react independently
    public function updated(Order $order): void
    {
        logger()->info('ORDER UPDATED', [
            'order_id' => $order->id,
            'dirty' => $order->getDirty(),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ]);

        if (! $order->isDirty('status')) {
            return;
        }

        OrderStatusChanged::dispatch(
            $order,
            $order->getOriginal('status') ?? 'none',
            $order->status,
        );
    }
}
