<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Application\Delivery\DeliveryCompany\Actions\ConnectCarrierAction;
use App\Application\Delivery\DeliveryCompany\Actions\DisconnectCarrierAction;
use App\Application\Delivery\DeliveryCompany\Actions\GetDeliveryCompanyAction;
use App\Application\Delivery\DeliveryCompany\Actions\ListDeliveryCompaniesAction;
use App\Application\Delivery\DeliveryCompany\Actions\RegisterCarrierWebhookAction;
use App\Application\Delivery\DeliveryCompany\Actions\TestCarrierConnectionAction;
use App\Application\Delivery\DeliveryCompany\Actions\UnregisterCarrierWebhookAction;
use App\Application\Delivery\DeliveryCompany\Commands\ConnectCarrierCommand;
use App\Application\Delivery\DeliveryCompany\Commands\DisconnectCarrierCommand;
use App\Application\Delivery\DeliveryCompany\Commands\RegisterCarrierWebhookCommand;
use App\Application\Delivery\DeliveryCompany\Commands\UnregisterCarrierWebhookCommand;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\DeliveryCompany\Exceptions\DeliveryCompanyNotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class DeliveryCompanyController extends Controller
{
    public function __construct(
        private readonly ListDeliveryCompaniesAction $listCompanies,
        private readonly GetDeliveryCompanyAction $getCompany,
        private readonly ConnectCarrierAction $connectCarrier,
        private readonly DisconnectCarrierAction $disconnectCarrier,
        private readonly RegisterCarrierWebhookAction $registerWebhook,
        private readonly UnregisterCarrierWebhookAction $unregisterWebhook,
        private readonly TestCarrierConnectionAction $testCarrierConnection,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companies = $this->listCompanies->execute(
            $request->filled('active') ? $request->boolean('active') : null
        );

        return response()->json(['companies' => $companies]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $company = $this->getCompany->execute((int) $id);

            return response()->json([
                'company' => $company,
                'is_connected' => (bool) $company->is_active && $company->api_key,
                'subscription_status' => [
                    'webhook_enabled' => (bool) $company->webhook_enabled,
                    'webhook_registered_at' => $company->webhook_registered_at,
                ],
            ]);
        } catch (DeliveryCompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function connect(Request $request, string $id): JsonResponse
    {
        Log::info('CONNECT CALLED', [
            'user_id' => $request->user()?->id,
            'role' => $request->user()?->role,
            'company_id' => $id,
            'payload' => $request->all(),
        ]);

        if (!$request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'field_mapping' => 'nullable|array',
        ]);

        if (!$request->user()->team_id) {
            return response()->json(['message' => 'User is not assigned to a team.'], 422);
        }

        try {
            $config = $this->connectCarrier->execute(new ConnectCarrierCommand(
                deliveryCompanyId: (int) $id,
                teamId: (int) $request->user()->team_id,
                apiKey: $validated['api_key'],
                apiSecret: $validated['api_secret'] ?? null,
                username: $validated['username'] ?? null,
                password: $validated['password'] ?? null,
                fieldMapping: $validated['field_mapping'] ?? null,
            ));

            return response()->json([
                'message' => 'Transporteur connecté avec succès.',
                'configuration' => $config,
            ]);
        } catch (DeliveryCompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Carrier connection failed', [
                'user_id' => $request->user()->id,
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to connect carrier. Please try again.'], 422);
        }
    }

    public function disconnect(Request $request, string $id): JsonResponse
    {
        if (!$request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$request->user()->team_id) {
            return response()->json(['message' => 'User is not assigned to a team.'], 422);
        }

        $this->disconnectCarrier->execute(new DisconnectCarrierCommand(
            deliveryCompanyId: (int) $id,
            teamId: (int) $request->user()->team_id,
        ));

        return response()->json(['message' => 'Transporteur déconnecté avec succès.']);
    }

    public function enableOrdersUpdates(Request $request, string $id): JsonResponse
    {
        if (!$request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$request->user()->team_id) {
            return response()->json(['message' => 'User is not assigned to a team.'], 422);
        }

        try {
            $this->registerWebhook->execute(new RegisterCarrierWebhookCommand(
                deliveryCompanyId: (int) $id,
                teamId: (int) $request->user()->team_id,
                host: $request->getHost(),
            ));

            return response()->json([
                'message' => 'Mise à jour des commandes activée. Enregistrement webhook en cours.',
            ]);
        } catch (CarrierNotConnectedException | DeliveryCompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function disableOrdersUpdates(Request $request, string $id): JsonResponse
    {
        if (!$request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$request->user()->team_id) {
            return response()->json(['message' => 'User is not assigned to a team.'], 422);
        }

        $this->unregisterWebhook->execute(new UnregisterCarrierWebhookCommand(
            deliveryCompanyId: (int) $id,
            teamId: (int) $request->user()->team_id,
        ));

        return response()->json(['message' => 'Mise à jour des commandes désactivée avec succès.']);
    }

    public function testConnection(string $id): JsonResponse
    {
        $connected = $this->testCarrierConnection->execute((int) $id);

        return response()->json([
            'connected' => $connected,
            'message' => $connected ? 'Connexion réussie.' : 'Erreur de connexion.',
        ], $connected ? 200 : 422);
    }

    public function actions(string $id): JsonResponse
    {
        return response()->json([
            'actions' => [
                [
                    'key' => 'createParcel',
                    'label' => 'Create Parcel',
                    'method' => 'POST',
                    'group' => 'MAIN ACTION',
                    'credentials' => [],
                    'fields' => [],
                    'saved_credentials' => [],
                    'saved_prefilled' => [],
                    'saved_hidden' => [],
                    'auto_create_enabled' => false,
                    'config_state' => 'configured',
                    'test_state' => 'pending',
                ],
                [
                    'key' => 'getStatus',
                    'label' => 'Get Status',
                    'method' => 'GET',
                    'group' => 'TRACKING',
                    'credentials' => [],
                    'fields' => [],
                    'saved_credentials' => [],
                    'saved_prefilled' => [],
                    'saved_hidden' => [],
                    'auto_create_enabled' => false,
                    'config_state' => 'configured',
                    'test_state' => 'pending',
                ],
                [
                    'key' => 'ordersUpdates',
                    'label' => 'Orders Updates',
                    'method' => 'WEBHOOK',
                    'group' => 'WEBHOOKS',
                    'credentials' => [],
                    'fields' => [],
                    'saved_credentials' => [],
                    'saved_prefilled' => [],
                    'saved_hidden' => [],
                    'auto_create_enabled' => false,
                    'config_state' => 'configured',
                    'test_state' => 'pending',
                    'webhook_url' => url("/api/webhooks/delivery/{$id}"),
                    'webhook_status' => 'pending',
                ],
            ]
        ]);
    }
}
