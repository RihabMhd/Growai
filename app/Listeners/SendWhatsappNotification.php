<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\OrderStatus;
use App\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWhatsappNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event — dispatches a queued job to send the WhatsApp message.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order     = $event->order;
        $newStatus = $event->newStatus;

        try {
            // 1. Look up the OrderStatus record and check the auto_send flag
            $status = OrderStatus::where('slug', $newStatus)->first();

            if (!$status || !$status->auto_send) {
                Log::info('WhatsApp skipped: auto_send is disabled', [
                    'order_id' => $order->id,
                    'status'   => $newStatus,
                ]);
                return;
            }

            // 2. Resolve the template for the team's preferred language
            $message     = $this->resolveMessage($status, $order);
            $phoneNumber = $order->client?->phone;

            if (empty($message) || empty($phoneNumber)) {
                Log::warning('WhatsApp skipped: missing message or phone', [
                    'order_id'     => $order->id,
                    'phone_empty'  => empty($phoneNumber),
                    'message_empty'=> empty($message),
                ]);
                return;
            }

            // 3. Dispatch a queued job (retries are handled there)
            SendWhatsappMessageJob::dispatch($phoneNumber, $message, $order->id);

            Log::info('WhatsApp job dispatched', [
                'order_id' => $order->id,
                'phone'    => $phoneNumber,
                'status'   => $newStatus,
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsApp dispatch failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveMessage(OrderStatus $status, object $order): string
    {
        $templates = $status->templates ?? [];

        $team = Team::first();
        $lang = $team?->whatsapp_language ?? 'FR';

        // Priority: team language → FR → legacy single field
        $template = $templates[$lang]
            ?? $templates['FR']
            ?? $status->whatsapp_message
            ?? '';

        return empty($template) ? '' : $this->replacePlaceholders($template, $order);
    }

    private function replacePlaceholders(string $template, object $order): string
    {
        $team = Team::first();

        $placeholders = [
            '{{order_id}}'       => $order->id,
            '{{status}}'         => $order->status,
            '{{customer_name}}'  => $order->client?->name   ?? '',
            '{{customer_phone}}' => $order->client?->phone  ?? '',
            '{{total}}'          => $order->total_price     ?? '',
            '{{shop_name}}'      => $team?->name            ?? config('app.name'),
            '{{product_name}}'   => $this->getFirstProductName($order),
            '{{currency}}'       => $order->currency        ?? 'MAD',
        ];

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template
        );
    }

    private function getFirstProductName(object $order): string
    {
        return $order->items()->with('product')->first()?->product?->name ?? '';
    }
}