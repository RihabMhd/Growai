<?php


namespace App\Domain\CarrierActions\ValueObjects;

final class FieldDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $required = false,
        public readonly mixed $default = null,
        public readonly array $options = [],
    ) {}

    public function toArray(bool $hidden = false): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'default' => $this->default,
            'hidden' => $hidden,
            'options' => $this->options,
        ];
    }
}