<?php


namespace App\Infrastructure\Carriers\Contracts;

interface CarrierHttpClient
{
    public function call(string $actionKey, string $method, array $payload): array;

    public function registerWebhook(string $url): array;
}