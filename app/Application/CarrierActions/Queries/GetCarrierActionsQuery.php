<?php
// app/Application/CarrierActions/Queries/GetCarrierActionsQuery.php

namespace App\Application\CarrierActions\Queries;

final class GetCarrierActionsQuery
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $teamId,
    ) {}
}