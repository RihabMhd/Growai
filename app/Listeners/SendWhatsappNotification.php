<?php

namespace App\Listeners;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Jobs\SendWhatsappMessageJob;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Teams\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendWhatsappNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderStatusChanged $event): void
    {
        $order     = $event->order;
        $newStatus = $event->newStatus;

        // prevents duplicate sending if the listener is accidentally fired multiple times
        $lockKey = "whatsapp_lock_{$order->id}_{$newStatus}";

        if (!Cache::add($lockKey, true, now()->addSeconds(30))) {
            Log::info('WhatsApp skipped: duplicate prevented by lock', [
                'order_id' => $order->id,
                'status'   => $newStatus,
            ]);
            return;
        }

        try {
            // check if auto_send is enabled for the status
            $status = OrderStatus::where('slug', $newStatus)->first();

            if (!$status || !$status->auto_send) {
                Log::info('WhatsApp skipped: auto_send is disabled', [
                    'order_id' => $order->id,
                    'status'   => $newStatus,
                ]);
                return;
            }

            // resolve the template for the team's preferred language
            $message     = $this->resolveMessage($status, $order);
            $phoneNumber = $order->client?->phone;

            if (empty($message) || empty($phoneNumber)) {
                Log::warning('WhatsApp skipped: missing message or phone', [
                    'order_id'      => $order->id,
                    'phone_empty'   => empty($phoneNumber),
                    'message_empty' => empty($message),
                ]);
                return;
            }

            // dispatch a queued job for retry handling
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



    private function resolveMessage(OrderStatus $status, object $order): string
    {
        $templates = $status->templates ?? [];

        $team = Team::first();
        $lang = $team?->whatsapp_language ?? 'FR';

        // priority: team language, fallback to fr, then legacy field
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
        return $order->items()->with('product')->first()?->product?->title ?? '';
    }
}