<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShipmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['slug' => 'label_created', 'name' => 'Label Created', 'color' => '#9CA3AF', 'position' => 1, 'is_final' => false],
            ['slug' => 'ready_for_pickup', 'name' => 'Ready for Pickup', 'color' => '#60A5FA', 'position' => 2, 'is_final' => false],
            ['slug' => 'picked_up', 'name' => 'Picked Up', 'color' => '#3B82F6', 'position' => 3, 'is_final' => false],
            ['slug' => 'out_for_delivery', 'name' => 'Out for Delivery', 'color' => '#F59E0B', 'position' => 4, 'is_final' => false],
            ['slug' => 'delivered', 'name' => 'Delivered', 'color' => '#10B981', 'position' => 5, 'is_final' => true],
            ['slug' => 'delayed', 'name' => 'Delayed', 'color' => '#F97316', 'position' => 6, 'is_final' => false],
            ['slug' => 'failure', 'name' => 'Failure', 'color' => '#EF4444', 'position' => 7, 'is_final' => true],
            ['slug' => 'returned', 'name' => 'Returned', 'color' => '#8B5CF6', 'position' => 8, 'is_final' => true],
        ];

        foreach ($statuses as $status) {
            DB::table('shipment_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                array_merge($status, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
