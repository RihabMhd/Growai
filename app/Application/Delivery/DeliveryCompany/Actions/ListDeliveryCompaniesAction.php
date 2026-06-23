<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;

final class ListDeliveryCompaniesAction
{
    public function execute(?bool $activeOnly = null): array
    {
        $query = DeliveryCompanyModel::query();

        if ($activeOnly !== null) {
            $query->where('is_active', $activeOnly);
        }

        return $query->get()->all();
    }
}
