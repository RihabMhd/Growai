<?php
// app/Application/CarrierActions/DTOs/ActionResponseDTO.php

namespace App\Application\CarrierActions\DTOs;

final class ActionResponseDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $category,
        public readonly string $method,
        public readonly array $credentials,
        public readonly array $fields,
        public readonly array $savedCredentials,
        public readonly array $savedPrefilled,
        public readonly array $savedHidden,
        public readonly ?bool $autoCreateEnabled,
        public readonly string $testStatus,
        public readonly ?array $lastResponse = null,
        public readonly ?string $lastError = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category,
            'method' => $this->method,
            'credentials' => $this->credentials,
            'fields' => $this->fields,
            'saved_credentials' => $this->savedCredentials,
            'saved_prefilled' => $this->savedPrefilled,
            'saved_hidden' => $this->savedHidden,
            'auto_create_enabled' => $this->autoCreateEnabled,
            'test_status' => $this->testStatus,
            'last_response' => $this->lastResponse,
            'last_error' => $this->lastError,
        ];
    }
}