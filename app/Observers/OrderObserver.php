<?php

namespace App\Observers;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    private static $dispatched = [];
    private static $callCount = 0;
    
    public function created(Order $order): void
    {
        Log::info('ORDER_CREATED', [
            'order_id' => $order->id,
            'status' => $order->status,
            'user_id' => auth()->id() ?? 'system'
        ]);
    }

    public function updated(Order $order): void
    {
        // Track how many times this method is called
        self::$callCount++;
        $callNumber = self::$callCount;
        
        Log::info('ORDER_OBSERVER_UPDATED_CALL', [
            'call_number' => $callNumber,
            'order_id' => $order->id,
            'dirty' => $order->getDirty(),
            'original' => $order->getOriginal(),
            'status_changed' => $order->isDirty('status'),
            'current_status' => $order->status,
            'original_status' => $order->getOriginal('status'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        // Skip if status didn't change
        if (!$order->isDirty('status')) {
            Log::info('STATUS_NOT_CHANGED_SKIPPING', [
                'order_id' => $order->id,
                'call_number' => $callNumber
            ]);
            return;
        }

        $oldStatus = $order->getOriginal('status') ?? 'none';
        $newStatus = $order->status;
        $changeKey = $order->id . '_' . $oldStatus . '_' . $newStatus;
        
        // Prevent duplicate dispatch
        if (in_array($changeKey, self::$dispatched)) {
            Log::warning('DUPLICATE_EVENT_DISPATCH_PREVENTED', [
                'order_id' => $order->id,
                'key' => $changeKey,
                'from' => $oldStatus,
                'to' => $newStatus,
                'call_number' => $callNumber
            ]);
            return;
        }
        
        self::$dispatched[] = $changeKey;

        Log::info('DISPATCHING_ORDER_STATUS_CHANGED', [
            'order_id' => $order->id,
            'from' => $oldStatus,
            'to' => $newStatus,
            'change_key' => $changeKey,
            'call_number' => $callNumber,
            'user_id' => auth()->id() ?? 'system'
        ]);

        // Dispatch domain event
        OrderStatusChanged::dispatch(
            $order,
            $oldStatus,
            $newStatus
        );
    }
}