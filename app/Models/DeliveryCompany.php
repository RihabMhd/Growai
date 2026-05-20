<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'api_url',
        'api_key',
        'credentials',
        'is_active',
        'webhook_enabled',
        'webhook_registered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'webhook_enabled' => 'boolean',
        'webhook_registered_at' => 'datetime',
    ];

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Check if the company is connected (has valid credentials)
     */
    public function isConnected(): bool
    {
        return !is_null($this->api_key) && $this->is_active;
    }

    /**
     * Get subscription/webhook status
     */
    public function getSubscriptionStatus(): array
    {
        return [
            'webhook_enabled' => $this->webhook_enabled ?? false,
            'webhook_registered_at' => $this->webhook_registered_at,
        ];
    }

    /**
     * Test connection to the carrier API
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->api_key) {
                return false;
            }

            // Make a simple API call to verify credentials
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->api_key),
            ])->get($this->api_url . '/test');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to test connection for company {$this->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Register webhook with the carrier for order updates
     */
    public function registerWebhook(string $host): array
    {
        try {
            if (!$this->isConnected()) {
                throw new \Exception('Carrier is not connected');
            }

            $webhookUrl = "https://{$host}/api/shipments/webhook/{$this->id}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->api_key),
            ])->post($this->api_url . '/webhooks', [
                'event' => 'tracking_update',
                'url' => $webhookUrl,
                'events' => ['picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed'],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to register webhook: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Failed to register webhook for company {$this->id}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Unregister webhook from carrier
     */
    public function unregisterWebhook(): void
    {
        try {
            if (!$this->isConnected() || !$this->webhook_enabled) {
                return;
            }

            Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->api_key),
            ])->delete($this->api_url . '/webhooks');
        } catch (\Exception $e) {
            Log::error("Failed to unregister webhook for company {$this->id}", ['error' => $e->getMessage()]);
            // Don't throw - webhook might already be unregistered
        }
    }

    /**
     * Create a parcel with the carrier
     */
    public function createParcel(Shipment $shipment, ?float $weight = null, ?array $dimensions = null): ?string
    {
        try {
            if (!$this->isConnected()) {
                throw new \Exception('Carrier is not connected');
            }

            $payload = [
                'recipient_name' => $shipment->recipient_name,
                'recipient_phone' => $shipment->recipient_phone,
                'address' => $shipment->address,
                'city' => $shipment->city,
                'region' => $shipment->region,
                'country' => $shipment->country,
                'cod_amount' => $shipment->cod_amount,
                'weight' => $weight,
                'dimensions' => $dimensions,
                'reference' => 'ORD-' . $shipment->order_id,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->api_key),
            ])->post($this->api_url . '/parcels', $payload);

            if (!$response->successful()) {
                throw new \Exception('Failed to create parcel: ' . $response->body());
            }

            $data = $response->json();
            return $data['tracking_number'] ?? $data['parcel_id'] ?? null;
        } catch (\Exception $e) {
            Log::error("Failed to create parcel for shipment {$shipment->id}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cancel a parcel with the carrier
     */
    public function cancelParcel(Shipment $shipment): bool
    {
        try {
            if (!$this->isConnected() || !$shipment->tracking_number) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->api_key),
            ])->delete($this->api_url . '/parcels/' . $shipment->tracking_number);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to cancel parcel {$shipment->tracking_number}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get tracking information for a shipment
     */
    public function getTracking(string $trackingNumber): array
    {
        try {
            if (!$this->isConnected()) {
                throw new \Exception('Carrier is not connected');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->api_key),
            ])->get($this->api_url . '/tracking/' . $trackingNumber);

            if (!$response->successful()) {
                throw new \Exception('Failed to get tracking info: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Failed to get tracking for {$trackingNumber}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

