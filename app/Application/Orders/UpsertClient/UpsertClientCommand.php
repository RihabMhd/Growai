<?php

namespace App\Application\Orders\UpsertClient;

final readonly class UpsertClientCommand
{
    public function __construct(
        public string $phone,
        public string $name,
        public ?string $email = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $street = null,
    ) {}
}