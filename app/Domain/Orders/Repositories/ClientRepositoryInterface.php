<?php

namespace App\Domain\Orders\Repositories;

use App\Domain\Clients\Models\Client;

/**
 * Domain contract for client persistence used during order creation/update.
 *
 * Bind in AppServiceProvider:
 *   $this->app->bind(ClientRepositoryInterface::class, EloquentClientRepository::class);
 */
interface ClientRepositoryInterface
{
    /**
     * Find or create a client by phone number, then update their details.
     * Phone is the natural key — always wins over any stored value.
     */
    public function upsertByPhone(
        string  $phone,
        string  $name,
        ?string $email,
        ?string $city,
        ?string $province,
        ?string $street,
    ): Client;

    /**
     * Update an existing client's details with only the provided fields.
     */
    public function update(Client $client, array $data): Client;
}