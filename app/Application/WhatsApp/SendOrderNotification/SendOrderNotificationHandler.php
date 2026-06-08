<?php 
namespace App\Application\WhatsApp\SendOrderNotification;

use App\Infrastructure\WhatsApp\WhatsAppServiceInterface;
use App\Models\{Order, OrderStatus, Team};

class SendOrderNotificationHandler
{
    public function __construct(
        private WhatsAppServiceInterface $whatsapp,
    ) {}

    public function handle(SendOrderNotificationCommand $cmd): void
    {
        $order  = Order::findOrFail($cmd->orderId);
        $status = OrderStatus::findOrFail($cmd->statusId);
        $lang   = Team::first()?->whatsapp_language ?? 'FR';

        $template = $status->templates[$lang]
            ?? $status->templates['FR']
            ?? $status->whatsapp_message
            ?? null;

        if (!$template || !$order->phone) {
            return;
        }

        $message = $this->renderTemplate($template, $order);
        $this->whatsapp->send($order->phone, $message);
    }

    private function renderTemplate(string $template, Order $order): string
    {
        return str_replace(
            ['{name}', '{order_id}', '{status}'],
            [$order->customer_name, $order->id, $order->status],
            $template
        );
    }
}