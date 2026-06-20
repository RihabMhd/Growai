<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Application\CarrierActions\Commands\RegisterWebhookCommand;
use App\Application\CarrierActions\Commands\RegisterWebhookHandler;
use App\Application\CarrierActions\Commands\SaveActionConfigCommand;
use App\Application\CarrierActions\Commands\SaveActionConfigHandler;
use App\Application\CarrierActions\Commands\TestActionCommand;
use App\Application\CarrierActions\Commands\TestActionHandler;
use App\Application\CarrierActions\Queries\GetCarrierActionsHandler;
use App\Application\CarrierActions\Queries\GetCarrierActionsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\SaveActionConfigRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CarrierActionController extends Controller
{
    public function index(int $id, GetCarrierActionsHandler $handler): JsonResponse
    {
        $dtos = $handler->handle(
            new GetCarrierActionsQuery($id, auth()->user()->team_id)
        );

        return response()->json(
            array_map(fn ($dto) => $dto->toArray(), $dtos)
        );
    }

    public function update(int $id, string $action, SaveActionConfigRequest $request, SaveActionConfigHandler $handler): JsonResponse
    {
        $handler->handle(new SaveActionConfigCommand(
            $id,
            $action,
            auth()->user()->team_id,
            $request->validated()
        ));

        return response()->json(['ok' => true]);
    }

    public function test(int $id, string $action, TestActionHandler $handler): JsonResponse
    {
        $result = $handler->handle(new TestActionCommand($id, $action, auth()->user()->team_id));

        return response()->json($result);
    }

    public function registerWebhook(int $id, RegisterWebhookHandler $handler): JsonResponse
    {
        $result = $handler->handle(new RegisterWebhookCommand($id, auth()->user()->team_id));

        return response()->json($result);
    }
}