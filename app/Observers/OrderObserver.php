<?php

namespace App\Observers;

use App\Models\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Team;
use App\Models\OrderHistory;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // 1. Fetch team settings
        $team = Team::first();
        if (!$team || !$team->dispatch_auto) {
            return;
        }

        // 2. Extract product IDs in the order
        $orderProductIds = $order->items()->pluck('product_id')->filter()->toArray();

        // 3. Find all active agents in dispatch
        $agents = User::where('role', 'staff')
            ->where('is_active', true)
            ->where('is_dispatch_active', true)
            ->where('quota', '>', 0)
            ->with('products')
            ->get();

        if ($agents->isEmpty()) {
            return;
        }

        // 4. Filter agents by product visibility eligibility
        $eligibleAgents = $agents->filter(function ($agent) use ($orderProductIds) {
            $assignedProductIds = $agent->products->pluck('id')->toArray();
            
            // If the agent has NO assigned products, they can handle all orders
            if (empty($assignedProductIds)) {
                return true;
            }

            // If they have assigned products, the order must contain at least one of their assigned products
            return !empty(array_intersect($orderProductIds, $assignedProductIds));
        });

        if ($eligibleAgents->isEmpty()) {
            return;
        }

        // 5. Query the number of orders currently assigned to each eligible agent
        $agentOrderCounts = Order::whereIn('assigned_to', $eligibleAgents->pluck('id'))
            ->selectRaw('assigned_to, count(*) as count')
            ->groupBy('assigned_to')
            ->pluck('count', 'assigned_to')
            ->toArray();

        // 6. Proportional Round-Robin selection
        $selectedAgent = null;
        $lowestRatio = null;

        // Split agents into those who haven't reached their quota, and those who have
        $underQuotaAgents = $eligibleAgents->filter(function ($agent) use ($agentOrderCounts) {
            $currentCount = $agentOrderCounts[$agent->id] ?? 0;
            return $currentCount < $agent->quota;
        });

        $candidates = $underQuotaAgents->isNotEmpty() ? $underQuotaAgents : $eligibleAgents;

        foreach ($candidates as $agent) {
            $currentCount = $agentOrderCounts[$agent->id] ?? 0;
            $ratio = $currentCount / $agent->quota;

            if ($selectedAgent === null || $ratio < $lowestRatio) {
                $selectedAgent = $agent;
                $lowestRatio = $ratio;
            } elseif ($ratio == $lowestRatio) {
                // Tie breaker: pick the one with fewer actual orders
                $currentSelectedCount = $agentOrderCounts[$selectedAgent->id] ?? 0;
                if ($currentCount < $currentSelectedCount) {
                    $selectedAgent = $agent;
                }
            }
        }

        if ($selectedAgent) {
            $order->updateQuietly([
                'assigned_to' => $selectedAgent->id
            ]);

            // Add history entry for auto-dispatch
            OrderHistory::create([
                'order_id' => $order->id,
                'user_id' => $selectedAgent->id,
                'action_type' => 'status',
                'old_value' => 'unassigned',
                'new_value' => 'assigned',
                'description' => "Commande assignée automatiquement à l'agent {$selectedAgent->name} (Auto-Dispatch Round-Robin)."
            ]);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if status has changed
        if ($order->isDirty('status')) {
            $newStatus = $order->status;
        
            // Log status change in history
            $oldStatus = $order->getOriginal('status') ?? 'none';
            OrderHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id() ?? $order->assigned_to,
                'action_type' => 'status',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'description' => "Statut de la commande modifié de '{$oldStatus}' à '{$newStatus}'."
            ]);

            $status = OrderStatus::where('slug', $newStatus)->first();
            if ($status && $status->auto_send && !empty($status->whatsapp_message)) {
                // Use the dedicated WhatsAppService to send the templated message
                $whatsappService = app('\\App\\Services\\WhatsAppService');
                // Simple placeholder replacement
                $placeholders = [
                    '{{order_id}}' => $order->id,
                    '{{status}}' => $newStatus,
                    '{{customer_name}}' => $order->customer_name ?? '',
                    '{{customer_phone}}' => $order->customer_phone ?? '',
                    '{{total}}' => $order->total_price ?? '',
                ];
                $message = str_replace(array_keys($placeholders), array_values($placeholders), $status->whatsapp_message);
                $whatsappService->send($order->customer_phone, $message);
            }

            // 1. Make sure order is assigned and commission is not already paid
            if ($order->assigned_to && !$order->commission_paid) {
                $agent = User::find($order->assigned_to);
                
                if ($agent && $agent->role === 'staff' && $agent->commission_trigger === $newStatus) {
                    $commissionAmount = 0.00;

                    if ($agent->commission_type === 'fixed') {
                        $commissionAmount = (float) $agent->commission_amount;
                    } elseif ($agent->commission_type === 'percent') {
                        $commissionAmount = ((float) $agent->commission_amount / 100) * (float) $order->total_price;
                    }

                    if ($commissionAmount > 0) {
                        // Credit agent wallet
                        $agent->increment('wallet_balance', $commissionAmount);
                        
                        // Mark commission as paid
                        $order->updateQuietly([
                            'commission_paid' => true
                        ]);

                        // Add history entry
                        $currency = $order->currency ?? 'DA';
                        OrderHistory::create([
                            'order_id' => $order->id,
                            'user_id' => $agent->id,
                            'action_type' => 'status',
                            'old_value' => '0',
                            'new_value' => (string) $commissionAmount,
                            'description' => "Commission de " . number_format($commissionAmount, 2, '.', '') . " " . $currency . " créditée automatiquement au portefeuille de l'agent {$agent->name}."
                        ]);
                    }
                }
            }
        }
    }
}
