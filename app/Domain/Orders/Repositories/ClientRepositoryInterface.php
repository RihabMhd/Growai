<?php

namespace App\Domain\Orders\Repositories;

use App\Domain\Clients\Models\Client;


interface ClientRepositoryInterface
{
    // phone is the natural key, it always wins over stored values
    public function upsertByPhone(
        string  $phone,
        string  $name,
        ?string $email,
        ?string $city,
        ?string $province,
        ?string $street,
    ): Client;


    public function update(Client $client, array $data): Client;
}