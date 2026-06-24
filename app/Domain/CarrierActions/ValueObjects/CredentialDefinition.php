<?php


namespace App\Domain\CarrierActions\ValueObjects;

final class CredentialDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type = 'password',
        public readonly bool $required = true,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
        ];
    }
}