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
use App\Domain\CarrierActions\CarrierActionDefinitionRegistry;
use App\Domain\CarrierActions\ValueObjects\ActionDefinition;
use App\Domain\Delivery\DeliveryCompany\Exceptions\CarrierNotConnectedException;
use App\Domain\Delivery\DeliveryCompany\Exceptions\DeliveryCompanyNotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        private readonly CarrierActionDefinitionRegistry $actionRegistry,
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
                'is_connected' => (bool) $company->isActive && $company->hasCredentials,
                'subscription_status' => [
                    'webhook_enabled' => (bool) $company->webhookEnabled,
                    'webhook_registered_at' => $company->webhookRegisteredAt,
                ],
            ]);
        } catch (DeliveryCompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function connect(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $request->user()->team_id) {
            return response()->json(['message' => 'User is not assigned to a team.'], 422);
        }

        // resolve company to get slug for registry lookup
        try {
            $company = $this->getCompany->execute((int) $id);
        } catch (DeliveryCompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        // build validation rules from the carrier's main action credential definitions
        try {
            $rules = $this->buildCredentialRules($company->slug);
        } catch (NotFoundHttpException) {
            // carrier not in registry, accept raw credentials
            $rules = [];
        }


        try {
            $validated = $this->validateCredentials($request->all(), $rules);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // separate field mapping from credentials
        $fieldMapping = is_array($validated['field_mapping'] ?? null)
            ? $validated['field_mapping']
            : null;

        unset($validated['field_mapping']);
        $credentials = $validated;

        try {
            $config = $this->connectCarrier->execute(new ConnectCarrierCommand(
                deliveryCompanyId: (int) $id,
                teamId: (int) $request->user()->team_id,
                credentials: $credentials,
                fieldMapping: $fieldMapping,
            ));

            return response()->json([
                'message' => 'Transporteur connecté avec succès.',
                'configuration' => $config,
            ]);
        } catch (DeliveryCompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Carrier connection failed', [
                'user_id'    => $request->user()->id,
                'company_id' => $id,
                'carrier'    => $company->slug,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to connect carrier. Please try again.'], 422);
        }
    }

    public function disconnect(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $request->user()->team_id) {
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
        if (! $request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $request->user()->team_id) {
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
        if (! $request->user()->role->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $request->user()->team_id) {
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
            'message'   => $connected ? 'Connexion réussie.' : 'Erreur de connexion.',
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
            ],
        ]);
    }



    // build laravel validation rules from the main action credential definitions
    private function buildCredentialRules(string $slug): array
    {
        $definitions = $this->actionRegistry->definitionsFor($slug);

        $rules = ['field_mapping' => 'nullable|array'];

        foreach ($definitions as $action) {
            if ($action->category !== ActionDefinition::CATEGORY_MAIN_ACTION) {
                continue;
            }

            foreach ($action->credentials as $cred) {
                $rules[$cred->key] = $cred->required ? 'required|string' : 'nullable|string';
            }

            break;
        }

        return $rules;
    }


    private function validateCredentials(array $input, array $rules): array
    {
        if (empty($rules)) {
            // no registry entry, accept anything that is a non-empty string
            return array_filter($input, fn($v) => is_string($v) && $v !== '');
        }

        return Validator::make($input, $rules)->validate();
    }
}