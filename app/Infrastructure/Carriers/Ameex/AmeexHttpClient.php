<?php

namespace App\Infrastructure\Carriers\Ameex;

use App\Infrastructure\Carriers\Contracts\CarrierHttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Illuminate\Support\Facades\Log;

final class AmeexHttpClient implements CarrierHttpClient
{
    private const BASE_URL = 'https://api.ameex.app';

    private const ENDPOINTS = [
        'createParcel' => '/customer/Delivery/Parcels/Action/Type/Add',
        'status' => '/customer/Delivery/Parcels/Statuts',
    ];

    public function __construct(private readonly array $credentials) {}

    public function call(string $actionKey, string $method, array $payload): array
    {
        $url = self::BASE_URL . ($this->resolveEndpoint($actionKey));
        Log::info('AMEEX REQUEST', [
            'action' => $actionKey,
            'method' => $method,
            'url' => $url,
        ]);

        $response = Http::withHeaders($this->authHeaders())
            ->{strtolower($method)}($url, $payload);
        Log::info('AMEEX RESPONSE', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        if (! $response->successful()) {
            throw new RuntimeException("Ameex action [{$actionKey}] failed: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function registerWebhook(string $url): array
    {
        throw new RuntimeException(
            'AMEEX does not support API-based webhook registration. '
                . 'Configure webhooks manually in the AMEEX dashboard (My Businesses → API → Webhooks).'
        );
    }

    private function resolveEndpoint(string $actionKey): string
    {
        return self::ENDPOINTS[$actionKey]
            ?? throw new RuntimeException("Unknown Ameex action key: {$actionKey}");
    }

    private function authHeaders(): array
    {
        return [
            'C-Api-Id' => $this->decrypt($this->credentials['api_id'] ?? null),
            'C-Api-Key' => $this->decrypt($this->credentials['api_key'] ?? null),
            'Accept' => 'application/json',
        ];
    }

    private function decrypt(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
