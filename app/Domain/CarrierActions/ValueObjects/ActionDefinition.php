<?php


namespace App\Domain\CarrierActions\ValueObjects;

final class ActionDefinition
{
    public const CATEGORY_MAIN_ACTION = 'main_action';
    public const CATEGORY_PROVINCE_CITY = 'province_city';
    public const CATEGORY_LOOKUP = 'lookup';
    public const CATEGORY_WEBHOOK = 'webhook';


    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $category,
        public readonly string $method,
        public readonly array $fields = [],
        public readonly array $credentials = [],
        public readonly bool $supportsAutoCreate = false,
        public readonly bool $supportsApiRegistration = true,
    ) {}
}