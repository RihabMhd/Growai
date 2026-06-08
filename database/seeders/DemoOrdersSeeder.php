<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Domain\Orders\Models\Order;

class DemoOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::create([
            'shop_id' => 1,
            'client_id' => 1,
            'order_number' => '#1001',
            'customer_name' => 'Karine Ruby',
            'customer_email' => 'karine@example.com',
            'customer_phone' => '+212600000000',
            'total_price' => 1009,
            'currency' => 'CAD',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'source_channel' => 'shopify',
        ]);
    }
}
