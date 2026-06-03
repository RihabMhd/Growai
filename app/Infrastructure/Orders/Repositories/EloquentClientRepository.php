<?php

namespace App\Infrastructure\Orders\Repositories;

use App\Domain\Clients\Models\Client;

class EloquentClientRepository implements ClientRepositoryInterface
{
    /**
     * Find or create a client by phone, then always update with latest data.
     *
     * The controller used firstOrCreate + immediate update, which had a subtle
     * bug: if the client existed, stale email/city values could persist because
     * nulls were skipped inconsistently. Here we always write the freshest data
     * for non-null fields, and preserve existing values for null inputs.
     */
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

        // Always update with the latest data supplied by the caller.
        // Preserve existing value when the incoming field is null.
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