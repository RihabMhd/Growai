<?php

namespace App\Domain\Delivery\Shipment\ValueObjects;

final readonly class Address
{
    public function __construct(
        public string $recipientName,
        public string $recipientPhone,
        public string $street,
        public ?string $city = null,
        public ?string $region = null,
        public string $country = 'MA',
    ) {}

    public function toArray(): array
    {
        return [
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipientPhone,
            'address' => $this->street,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
        ];
    }
}
