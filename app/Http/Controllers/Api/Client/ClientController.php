<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Domain\Clients\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        try {
            $sort   = $request->get('sort',   'recent');
            $search = $request->get('search', '');

            // ── Build base query with raw aggregates ──────────────────────────
            // Uses raw DB subqueries so it works even if Order model or its
            // relationships are not fully set up yet.
            $query = Client::query()
                ->select('clients.*')
                ->selectRaw('(SELECT COUNT(*) FROM orders WHERE orders.client_id = clients.id) as orders_count')
                ->selectRaw('(SELECT COALESCE(SUM(total_price),0) FROM orders WHERE orders.client_id = clients.id) as orders_total')
                ->selectRaw('(SELECT created_at FROM orders WHERE orders.client_id = clients.id ORDER BY created_at DESC LIMIT 1) as last_order_at');

            // ── Search ────────────────────────────────────────────────────────
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('clients.name',  'like', "%{$search}%")
                      ->orWhere('clients.phone','like', "%{$search}%")
                      ->orWhere('clients.email','like', "%{$search}%")
                      ->orWhere('clients.city', 'like', "%{$search}%");
                });
            }

            // ── Sort ──────────────────────────────────────────────────────────
            match ($sort) {
                'most_orders'  => $query->orderByDesc('orders_count'),
                'top_spenders' => $query->orderByDesc('orders_total'),
                'name_az'      => $query->orderBy('clients.name'),
                default        => $query->orderByDesc('clients.created_at'),
            };

            $clients = $query->get()->map(function ($client) {
                $lastOrderAt = $client->last_order_at;
                $lastOrderHuman = 'Never';
                if ($lastOrderAt) {
                    try {
                        $lastOrderHuman = \Carbon\Carbon::parse($lastOrderAt)->diffForHumans();
                    } catch (\Exception $e) {
                        $lastOrderHuman = $lastOrderAt;
                    }
                }

                return [
                    'id'          => $client->id,
                    'name'        => $client->name,
                    'email'       => $client->email,
                    'phone'       => $client->phone,
                    'city'        => $client->city,
                    'address'     => $client->address,
                    'orders'      => (int) $client->orders_count,
                    'total_spent' => number_format((float) $client->orders_total, 2) . ' MAD',
                    'last_order'  => $lastOrderHuman,
                    'created_at'  => $client->created_at,
                ];
            });

            // ── Metrics (raw so no model dependency) ─────────────────────────
            $totalClients = DB::table('clients')->count();
            $totalOrders  = DB::table('orders')->count();
            $totalRevenue = DB::table('orders')->sum('total_price');

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'metrics' => [
                    'total_clients' => $totalClients,
                    'total_orders'  => $totalOrders,
                    'total_revenue' => number_format((float) $totalRevenue, 2) . ' MAD',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load clients.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $client = Client::findOrFail($id);

            $orders = DB::table('orders')
                ->where('client_id', $id)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => array_merge($client->toArray(), [
                    'orders_count' => $orders->count(),
                    'orders_total' => $orders->sum('total_price'),
                    'orders'       => $orders,
                ]),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $client    = Client::findOrFail($id);
            $validated = $request->validate([
                'name'    => 'sometimes|string|max:255',
                'email'   => 'sometimes|nullable|email|max:255',
                'phone'   => 'sometimes|nullable|string|max:30',
                'city'    => 'sometimes|nullable|string|max:100',
                'address' => 'sometimes|nullable|string',
                'notes'   => 'sometimes|nullable|string',
            ]);

            $client->update($validated);

            return response()->json(['success' => true, 'message' => 'Client updated.', 'data' => $client->fresh()]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Client::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Client deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}