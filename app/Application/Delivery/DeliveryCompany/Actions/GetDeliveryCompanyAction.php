<?php

namespace App\Application\Delivery\DeliveryCompany\Actions;

use App\Domain\Delivery\DeliveryCompany\Exceptions\DeliveryCompanyNotFoundException;
use App\Infrastructure\Delivery\Persistence\Eloquent\Models\DeliveryCompanyModel;

final class GetDeliveryCompanyAction
{
    public function execute(int $id): DeliveryCompanyModel
    {
        $company = DeliveryCompanyModel::find($id);

        if (! $company) {
            throw DeliveryCompanyNotFoundException::withId($id);
        }

        return $company;
    }
}
