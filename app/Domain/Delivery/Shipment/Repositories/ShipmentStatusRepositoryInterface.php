<?php

namespace App\Domain\Delivery\Shipment\Repositories;

use App\Domain\Delivery\Shipment\Entities\ShipmentStatus;

interface ShipmentStatusRepositoryInterface
{
    public function findBySlug(string $slug): ?ShipmentStatus;

    public function findById(int $id): ?ShipmentStatus;

    public function all(): array;
}
