<?php

namespace App\Listeners;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Models\OrderHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LogOrderHistory
{
    private static $processed = [];
    private static $callCount = 0;
    
    public function handle(OrderStatusChanged $event): void
    {
        // Track listener calls
        self::$callCount++;
        $callNumber = self::$callCount;
        
        // Generate unique key for this status change
        $changeKey = $event->order->id . '_' . $event->oldStatus . '_' . $event->newStatus;
        
        Log::info('LOG_ORDER_HISTORY_LISTENER_CALLED', [
            'call_number' => $callNumber,
            'order_id' => $event->order->id,
            'old' => $event->oldStatus,
            'new' => $event->newStatus,
            'change_key' => $changeKey,
            'already_processed' => in_array($changeKey, self::$processed),
        ]);
        
        // Prevent duplicate processing within the same request
        if (in_array($changeKey, self::$processed)) {
            Log::warning('DUPLICATE_HISTORY_PREVENTED_IN_LISTENER', [
                'order_id' => $event->order->id,
                'key' => $changeKey,
                'call_number' => $callNumber
            ]);
            return;
        }
        
        self::$processed[] = $changeKey;
        
        try {
            // Create history entry
            $history = OrderHistory::create([
                'order_id' => $event->order->id,
                'user_id' => Auth::id() ?? 1,
                'action_type' => 'status_changed',
                'old_value' => $event->oldStatus,
                'new_value' => $event->newStatus,
                'description' => "Statut modifié de '{$event->oldStatus}' à '{$event->newStatus}'",
            ]);
            
            Log::info('ORDER_HISTORY_SAVED', [
                'order_id' => $event->order->id,
                'history_id' => $history->id,
                'old' => $event->oldStatus,
                'new' => $event->newStatus,
                'call_number' => $callNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('FAILED_TO_SAVE_ORDER_HISTORY', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}