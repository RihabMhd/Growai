<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Domain\Shipments\Models\DeliveryCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryCompanyController extends Controller
{
    /**
     * List all delivery companies
     */
    public function index(Request $request)
    {
        $query = DeliveryCompany::query();

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $companies = $query->get();

        return response()->json([
            'companies' => $companies,
        ]);
    }

    /**
     * Get a specific delivery company with connection status
     */
    public function show(string $id)
    {
        $company = DeliveryCompany::findOrFail($id);

        return response()->json([
            'company' => $company,
            'is_connected' => $company->isConnected(),
            'subscription_status' => $company->getSubscriptionStatus(),
        ]);
    }

    /**
     * Connect a delivery company (store credentials)
     */
    public function connect(Request $request, string $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = DeliveryCompany::findOrFail($id);

        $validated = $request->validate([
            'api_key'    => 'required|string',
            'api_secret' => 'nullable|string',
            'username'   => 'nullable|string',
            'password'   => 'nullable|string',
        ]);

        // Store credentials securely (encrypted in the database)
        $company->update([
            'api_key' => encrypt($validated['api_key']),
            'credentials' => json_encode([
                'api_secret' => isset($validated['api_secret']) ? encrypt($validated['api_secret']) : null,
                'username' => isset($validated['username']) ? encrypt($validated['username']) : null,
                'password' => isset($validated['password']) ? encrypt($validated['password']) : null,
            ]),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Transporteur connecté avec succès.',
            'company' => $company,
        ]);
    }

    /**
     * Disconnect a delivery company
     */
    public function disconnect(string $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = DeliveryCompany::findOrFail($id);
        $company->update([
            'api_key' => null,
            'credentials' => null,
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Transporteur déconnecté avec succès.',
            'company' => $company,
        ]);
    }

    /**
     * Enable orders updates subscription with the carrier
     */
    public function enableOrdersUpdates(Request $request, string $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = DeliveryCompany::findOrFail($id);

        if (!$company->isConnected()) {
            return response()->json([
                'message' => 'Le transporteur n\'est pas connecté.',
            ], 422);
        }

        // Register the webhook with the carrier
        try {
            $result = $company->registerWebhook($request->getHost());
            
            $company->update([
                'webhook_enabled' => true,
                'webhook_registered_at' => now(),
            ]);

            return response()->json([
                'message' => 'Mise à jour des commandes activée avec succès.',
                'company' => $company,
                'webhook_result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to enable orders updates for company {$id}", ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Erreur lors de l\'activation des mises à jour: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Disable orders updates subscription
     */
    public function disableOrdersUpdates(string $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = DeliveryCompany::findOrFail($id);

        try {
            // Unregister webhook with carrier
            $company->unregisterWebhook();

            $company->update([
                'webhook_enabled' => false,
            ]);

            return response()->json([
                'message' => 'Mise à jour des commandes désactivée avec succès.',
                'company' => $company,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to disable orders updates for company {$id}", ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Erreur lors de la désactivation des mises à jour: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test connection to delivery company
     */
    public function testConnection(string $id)
    {
        $company = DeliveryCompany::findOrFail($id);

        try {
            $isConnected = $company->testConnection();

            return response()->json([
                'connected' => $isConnected,
                'message' => $isConnected ? 'Connexion réussie.' : 'Erreur de connexion.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'connected' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 422);
        }
    }
}
