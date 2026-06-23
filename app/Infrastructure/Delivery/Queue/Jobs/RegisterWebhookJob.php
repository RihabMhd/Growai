<?php

namespace App\Infrastructure\Delivery\Queue\Jobs;

use App\Domain\Delivery\DeliveryCompany\Repositories\DeliveryCompanyRepositoryInterface;
use App\Infrastructure\Delivery\Carriers\CarrierManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RegisterWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $deliveryCompanyId,
        public int $teamId,
        public string $host,
    ) {}

    public function handle(
        CarrierManager $carrierManager,
        DeliveryCompanyRepositoryInterface $companies,
    ): void {
        $carrier = $carrierManager->resolve($this->deliveryCompanyId, $this->teamId, $this->host);

        if ($carrier->registerWebhook()) {
            $companies->updateWebhookState(
                id: $this->deliveryCompanyId,
                enabled: true,
                registeredAt: new \DateTimeImmutable,
            );
        }
    }
}
