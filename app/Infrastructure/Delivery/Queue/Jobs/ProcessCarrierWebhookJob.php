<?php

namespace App\Infrastructure\Delivery\Queue\Jobs;

use App\Application\Delivery\Shipment\Handlers\ProcessCarrierWebhookHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessCarrierWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $webhookLogId,
        public int $deliveryCompanyId,
        public ?string $signature = null,
    ) {}

    public function handle(ProcessCarrierWebhookHandler $handler): void
    {
        $handler->handle($this->webhookLogId, $this->deliveryCompanyId, $this->signature);
    }
}
