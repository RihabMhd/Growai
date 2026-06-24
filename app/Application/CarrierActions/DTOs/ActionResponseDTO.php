<?php


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
        public readonly int $requiredFields = 0,
        public readonly int $configuredFields = 0,
        public readonly array $missingFields = [],
        public readonly int $completionPercent = 100,
        public readonly bool $readyForAutoCreate = false,
        public readonly bool $supportsApiRegistration = true,
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
            'required_fields' => $this->requiredFields,
            'configured_fields' => $this->configuredFields,
            'missing_fields' => $this->missingFields,
            'completion_percent' => $this->completionPercent,
            'ready_for_auto_create' => $this->readyForAutoCreate,
            'supports_api_registration' => $this->supportsApiRegistration,
        ];
    }
}
