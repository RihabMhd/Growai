<?php


namespace App\Infrastructure\Carriers\Ameex;

use App\Infrastructure\Carriers\Contracts\CarrierHttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class AmeexHttpClient implements CarrierHttpClient
{
    private const BASE_URL = 'https://api.ameex.example.com'; // TODO: confirm real base URL

    public function __construct(private readonly array $credentials) {}

    public function call(string $actionKey, string $method, array $payload): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->{strtolower($method)}(self::BASE_URL . '/' . ltrim($actionKey, '/'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException("Ameex action [{$actionKey}] failed: " . $response->body());
        }

        return $response->json() ?? [];
    }

    public function registerWebhook(string $url): array
    {
        return $this->call('webhook_ameex', 'POST', ['url' => $url]);
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
            return $value; // not encrypted (e.g. test fixture)
        }
    }
}