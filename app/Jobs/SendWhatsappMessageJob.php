<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
        public readonly int    $orderId,
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        $response = $whatsapp->send($this->phoneNumber, $this->message);

        if ($response->failed()) {
            Log::error('WhatsApp job: Twilio returned an error', [
                'order_id'    => $this->orderId,
                'phone'       => $this->phoneNumber,
                'status_code' => $response->status(),
                'body'        => $response->body(),
            ]);

            // let laravel retry the job
            $this->fail($response->body());
            return;
        }

        Log::info('WhatsApp job: message delivered', [
            'order_id'    => $this->orderId,
            'phone'       => $this->phoneNumber,
            'status_code' => $response->status(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('WhatsApp job permanently failed', [
            'order_id' => $this->orderId,
            'phone'    => $this->phoneNumber,
            'error'    => $e->getMessage(),
        ]);
    }
}