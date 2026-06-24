<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Clients\Models\Client;
use App\Domain\Orders\Repositories\ClientRepositoryInterface;

class EloquentClientRepository implements ClientRepositoryInterface
{
    // find or create client by phone, preserving existing non-null fields
    public function upsertByPhone(
        string  $phone,
        string  $name,
        ?string $email,
        ?string $city,
        ?string $province,
        ?string $street,
    ): Client {
        $client = Client::firstOrCreate(
            ['phone' => $phone],
            [
                'name'    => $name,
                'email'   => $email,
                'city'    => $city,
                'province' => $province,
                'address' => $street,
            ]
        );

        // always update with freshest data, preserving existing value when incoming is null
        $client->update([
            'name'     => $name,
            'email'    => $email    ?? $client->email,
            'city'     => $city     ?? $client->city,
            'province' => $province ?? $client->province,
            'address'  => $street   ?? $client->address,
        ]);

        return $client->fresh();
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->fresh();
    }
}