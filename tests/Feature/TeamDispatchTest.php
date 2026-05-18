<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Models\Client;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected $client;
    protected $shop;
    protected $products;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base settings
        $this->client = Client::create([
            'name' => 'Client Test',
            'email' => 'client@test.com'
        ]);

        $this->shop = Shop::create([
            'client_id' => $this->client->id,
            'name' => 'Shop Test',
            'url' => 'https://test.shopify.com'
        ]);

        // Seed products
        $this->products = [
            Product::create([
                'name' => 'iPhone 15',
                'sku' => 'IP15',
                'price' => 1000.00,
                'stock' => 50,
                'source_type' => 'manual'
            ]),
            Product::create([
                'name' => 'Samsung S24',
                'sku' => 'S24',
                'price' => 900.00,
                'stock' => 50,
                'source_type' => 'manual'
            ])
        ];
    }

    /**
     * Test auto-dispatch round-robin with quota weights.
     */
    public function test_auto_dispatch_round_robin_with_quotas(): void
    {
        // 1. Create a Team and enable Auto-Dispatch
        $team = Team::create([
            'dispatch_auto' => true
        ]);

        // 2. Create two agents with quotas (Agent A has quota 2, Agent B has quota 1)
        $agentA = User::factory()->create([
            'team_id' => $team->id,
            'name' => 'Agent A',
            'role' => 'staff',
            'is_active' => true,
            'is_dispatch_active' => true,
            'quota' => 2
        ]);

        $agentB = User::factory()->create([
            'team_id' => $team->id,
            'name' => 'Agent B',
            'role' => 'staff',
            'is_active' => true,
            'is_dispatch_active' => true,
            'quota' => 1
        ]);

        // 3. Create 3 orders
        // Each creation should trigger auto-dispatch. Let's create orders.
        $order1 = Order::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'order_number' => 'ORD-1',
            'customer_name' => 'John Doe',
            'total_price' => 1010.00,
            'currency' => 'DA',
            'status' => 'pending'
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $this->products[0]->id,
            'product_name' => $this->products[0]->name,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'total_price' => 1000.00
        ]);
        // Trigger manually or via event (observer)
        $order1->save(); // will trigger "created" event because observers are registered

        $order2 = Order::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'order_number' => 'ORD-2',
            'customer_name' => 'John Doe',
            'total_price' => 910.00,
            'currency' => 'DA',
            'status' => 'pending'
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $this->products[1]->id,
            'product_name' => $this->products[1]->name,
            'quantity' => 1,
            'unit_price' => 900.00,
            'total_price' => 900.00
        ]);
        $order2->save();

        $order3 = Order::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'order_number' => 'ORD-3',
            'customer_name' => 'John Doe',
            'total_price' => 1010.00,
            'currency' => 'DA',
            'status' => 'pending'
        ]);
        OrderItem::create([
            'order_id' => $order3->id,
            'product_id' => $this->products[0]->id,
            'product_name' => $this->products[0]->name,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'total_price' => 1000.00
        ]);
        $order3->save();

        // 4. Assert assignment (Agent A has quota 2, Agent B has quota 1)
        // With 3 orders, Agent A should get 2 and Agent B should get 1.
        $countA = Order::where('assigned_to', $agentA->id)->count();
        $countB = Order::where('assigned_to', $agentB->id)->count();

        $this->assertEquals(2, $countA);
        $this->assertEquals(1, $countB);
    }

    /**
     * Test agent visibility constraints.
     */
    public function test_agent_product_filtering(): void
    {
        // 1. Create a Team
        $team = Team::create();

        // 2. Agent A with iPhone assigned
        $agentA = User::factory()->create([
            'team_id' => $team->id,
            'role' => 'staff',
            'is_active' => true
        ]);
        $agentA->products()->attach($this->products[0]->id);

        // 3. Agent B with NO products assigned
        $agentB = User::factory()->create([
            'team_id' => $team->id,
            'role' => 'staff',
            'is_active' => true
        ]);

        // 4. Create an iPhone order and a Samsung order
        $iphoneOrder = Order::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'order_number' => 'ORD-IP',
            'customer_name' => 'John Doe',
            'total_price' => 1000.00,
            'status' => 'pending'
        ]);
        OrderItem::create([
            'order_id' => $iphoneOrder->id,
            'product_id' => $this->products[0]->id,
            'product_name' => $this->products[0]->name,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'total_price' => 1000.00
        ]);

        $samsungOrder = Order::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'order_number' => 'ORD-SAM',
            'customer_name' => 'John Doe',
            'total_price' => 900.00,
            'status' => 'pending'
        ]);
        OrderItem::create([
            'order_id' => $samsungOrder->id,
            'product_id' => $this->products[1]->id,
            'product_name' => $this->products[1]->name,
            'quantity' => 1,
            'unit_price' => 900.00,
            'total_price' => 900.00
        ]);

        // 5. Query as Agent A (should only see iPhone order)
        $responseA = $this->actingAs($agentA)->getJson('/api/auth/orders');
        $responseA->assertStatus(200);
        $ordersA = $responseA->json('orders');
        
        $this->assertCount(1, $ordersA);
        $this->assertEquals('ORD-IP', $ordersA[0]['order_number']);

        // 6. Query as Agent B (should see both orders since they have no product restrictions)
        $responseB = $this->actingAs($agentB)->getJson('/api/auth/orders');
        $responseB->assertStatus(200);
        $ordersB = $responseB->json('orders');

        $this->assertCount(2, $ordersB);
    }

    /**
     * Test automatically credited commissions.
     */
    public function test_commission_credited_automatically(): void
    {
        $team = Team::create();

        // Create Agent with commission trigger
        $agent = User::factory()->create([
            'team_id' => $team->id,
            'role' => 'staff',
            'commission_trigger' => 'confirmed',
            'commission_type' => 'fixed',
            'commission_amount' => 50.00,
            'wallet_balance' => 0.00
        ]);

        // Create order assigned to this agent
        $order = Order::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'order_number' => 'ORD-COM',
            'customer_name' => 'John Doe',
            'total_price' => 100.00,
            'status' => 'pending',
            'assigned_to' => $agent->id
        ]);

        // Update status to "confirmed" (triggers observer)
        $order->update(['status' => 'confirmed']);

        // Assert commission was credited
        $agent->refresh();
        $order->refresh();

        $this->assertEquals(50.00, $agent->wallet_balance);
        $this->assertTrue($order->commission_paid);

        // Update status again to "delivered" (should NOT trigger commission again)
        $order->update(['status' => 'delivered']);
        
        $agent->refresh();
        $this->assertEquals(50.00, $agent->wallet_balance); // remains 50
    }
}
