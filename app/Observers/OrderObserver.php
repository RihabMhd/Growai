<?php

namespace App\Observers;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Team;
use App\Models\User;
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

            if (empty($assignedProductIds)) {
                return true;
            }

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
        $lowestRatio   = null;

        $underQuotaAgents = $eligibleAgents->filter(function ($agent) use ($agentOrderCounts) {
            $currentCount = $agentOrderCounts[$agent->id] ?? 0;
            return $currentCount < $agent->quota;
        });

        $candidates = $underQuotaAgents->isNotEmpty() ? $underQuotaAgents : $eligibleAgents;

        foreach ($candidates as $agent) {
            $currentCount = $agentOrderCounts[$agent->id] ?? 0;
            $ratio        = $currentCount / $agent->quota;

            if ($selectedAgent === null || $ratio < $lowestRatio) {
                $selectedAgent = $agent;
                $lowestRatio   = $ratio;
            } elseif ($ratio == $lowestRatio) {
                $currentSelectedCount = $agentOrderCounts[$selectedAgent->id] ?? 0;
                if ($currentCount < $currentSelectedCount) {
                    $selectedAgent = $agent;
                }
            }
        }

        if ($selectedAgent) {
            $order->updateQuietly([
                'assigned_to' => $selectedAgent->id,
            ]);

            OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => $selectedAgent->id,
                'action_type' => 'status',
                'old_value'   => 'unassigned',
                'new_value'   => 'assigned',
                'description' => "Commande assignée automatiquement à l'agent {$selectedAgent->name} (Auto-Dispatch Round-Robin).",
            ]);
        }
    }

    /**
     * Handle the Order "updated" event.
     *
     * WhatsApp notifications are handled entirely by SendWhatsappNotification
     * listener via the OrderStatusChanged event — do NOT add direct WhatsApp
     * calls here to avoid duplicate messages.
     */
    public function updated(Order $order): void
    {
        if (!$order->isDirty('status')) {
            return;
        }

        $newStatus = $order->status;
        $oldStatus = $order->getOriginal('status') ?? 'none';

        // ── 1. Log status change in history ──────────────────────────────────
        OrderHistory::create([
            'order_id'    => $order->id,
            'user_id'     => auth()->id() ?? $order->assigned_to,
            'action_type' => 'status',
            'old_value'   => $oldStatus,
            'new_value'   => $newStatus,
            'description' => "Statut de la commande modifié de '{$oldStatus}' à '{$newStatus}'.",
        ]);

        // ── 2. Fire event — triggers broadcast + WhatsApp via listener ────────
        OrderStatusChanged::dispatch($order, $oldStatus, $newStatus);

        // ── 3. Commission handling ────────────────────────────────────────────
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
                    $agent->increment('wallet_balance', $commissionAmount);

                    $order->updateQuietly(['commission_paid' => true]);

                    $currency = $order->currency ?? 'MAD';
                    OrderHistory::create([
                        'order_id'    => $order->id,
                        'user_id'     => $agent->id,
                        'action_type' => 'status',
                        'old_value'   => '0',
                        'new_value'   => (string) $commissionAmount,
                        'description' => "Commission de " . number_format($commissionAmount, 2, '.', '') . " {$currency} créditée automatiquement au portefeuille de l'agent {$agent->name}.",
                    ]);
                }
            }
        }
    }
}