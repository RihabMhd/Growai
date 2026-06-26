<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Application\Delivery\DeliveryCompany\Actions\ListDeliveryCompaniesAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ActiveDeliveryCompaniesController extends Controller
{
    public function __construct(
        private readonly ListDeliveryCompaniesAction $listCompanies,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companies = $this->listCompanies->execute(true);

        $light = array_map(function ($c) {
            return [
                'id'    => (int) $c->id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'phone' => $c->phone,
                'api_url' => $c->api_url,
            ];
        }, $companies);

        return response()->json([
            'companies' => $light,
        ]);
    }
}

