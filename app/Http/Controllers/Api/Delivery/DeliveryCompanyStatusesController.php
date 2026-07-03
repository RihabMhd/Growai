<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Application\Delivery\DeliveryCompany\Actions\GetAllDeliveryCompanyStatusesAction;
use App\Application\Delivery\DeliveryCompany\Actions\GetDeliveryCompanyStatusesAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeliveryCompanyStatusesController extends Controller
{
    public function __construct(
        private readonly GetDeliveryCompanyStatusesAction $getStatuses,
        private readonly GetAllDeliveryCompanyStatusesAction $getAllStatuses,
    ) {}

    public function index(Request $request, string $id): JsonResponse
    {
        try {
            $statuses = $this->getStatuses->execute((int) $id);

            return response()->json($statuses);
        } catch (\RuntimeException $e) {
            // Keep consistent API behavior for unknown companies.
            throw new NotFoundHttpException($e->getMessage());
        }
    }

    public function all(): JsonResponse
    {
        $statuses = $this->getAllStatuses->execute();

        return response()->json($statuses);
    }
}

