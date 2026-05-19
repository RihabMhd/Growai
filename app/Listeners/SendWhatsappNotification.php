<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\OrderStatus;
use App\Models\Team;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWhatsappNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event - sends WhatsApp message when order status changes.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $newStatus = $event->newStatus;

        try {
            // 1. Find the OrderStatus record to check auto_send flag
            $status = OrderStatus::where('slug', $newStatus)->first();

            if (!$status || !$status->auto_send) {
                Log::info('WhatsApp skipped: auto_send is disabled', [
                    'order_id' => $order->id,
                    'status' => $newStatus,
                ]);
                return;
            }

            // 2. Resolve the message template
            $message = $this->resolveMessage($status, $order);
            $phoneNumber = $order->client?->phone;

            if (empty($message) || empty($phoneNumber)) {
                Log::warning('WhatsApp skipped: missing message or phone', [
                    'order_id' => $order->id,
                    'phone_empty' => empty($phoneNumber),
                    'message_empty' => empty($message),
                ]);
                return;
            }

            // 3. Send via WhatsApp
            $whatsappService = app(WhatsAppService::class);
            $response = $whatsappService->send($phoneNumber, $message);

            Log::info('WhatsApp message sent successfully', [
                'order_id' => $order->id,
                'phone' => $phoneNumber,
                'status_code' => $response->status(),
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Resolve the WhatsApp message for this status + order.
     */
    private function resolveMessage(OrderStatus $status, object $order): string
    {
        $templates = $status->templates ?? [];

        // Global language: stored on the Team, falls back to FR
        $team = Team::first();
        $lang = $team?->whatsapp_language ?? 'FR';

        // Pick best template: chosen language → FR fallback → legacy single message
        $template = $templates[$lang]
            ?? $templates['FR']
            ?? $status->whatsapp_message
            ?? '';

        if (empty($template)) {
            return '';
        }

        return $this->replacePlaceholders($template, $order);
    }

    /**
     * Replace all {{placeholder}} tokens in a template string.
     */
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

    /**
     * Get the name of the first product in the order.
     */
    private function getFirstProductName(object $order): string
    {
        return $order->items()->with('product')->first()?->product?->name ?? '';
    }
}
