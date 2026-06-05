<?php

namespace App\Application\Orders\UseCases\UpsertClient;

use App\Domain\Clients\Models\Client;
use App\Infrastructure\Orders\Repositories\ClientRepositoryInterface;

final readonly class UpsertClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clients
    ) {}

    public function handle(
        UpsertClientCommand $command
    ): Client {

        return $this->clients->upsertByPhone(
            phone: $command->phone,
            name: $command->name,
            email: $command->email,
            city: $command->city,
            province: $command->province,
            street: $command->street,
        );
    }
}