<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $canonicalStatuses = [
            ['slug' => 'unfulfilled',        'name' => 'Unfulfilled',        'color' => '#6B7280', 'position' => 1,  'is_final' => false],
            ['slug' => 'label_created',      'name' => 'Label Created',      'color' => '#9CA3AF', 'position' => 2,  'is_final' => false],
            ['slug' => 'label_purchased',    'name' => 'Label Purchased',    'color' => '#818CF8', 'position' => 3,  'is_final' => false],
            ['slug' => 'label_printed',      'name' => 'Label Printed',      'color' => '#6366F1', 'position' => 4,  'is_final' => false],
            ['slug' => 'confirmed',          'name' => 'Confirmed',          'color' => '#22C55E', 'position' => 5,  'is_final' => false],
            ['slug' => 'in_transit',         'name' => 'In Transit',         'color' => '#3B82F6', 'position' => 6,  'is_final' => false],
            ['slug' => 'out_for_delivery',   'name' => 'Out for Delivery',   'color' => '#F59E0B', 'position' => 7,  'is_final' => false],
            ['slug' => 'delivered',          'name' => 'Delivered',          'color' => '#10B981', 'position' => 8,  'is_final' => true],
            ['slug' => 'attempted_delivery', 'name' => 'Attempted Delivery', 'color' => '#F97316', 'position' => 9,  'is_final' => false],
            ['slug' => 'delivery_failed',    'name' => 'Delivery Failed',    'color' => '#EF4444', 'position' => 10, 'is_final' => true],
            ['slug' => 'delayed',            'name' => 'Delayed',            'color' => '#F97316', 'position' => 11, 'is_final' => false],
            ['slug' => 'returned',           'name' => 'Returned',           'color' => '#8B5CF6', 'position' => 12, 'is_final' => true],
            ['slug' => 'partial',            'name' => 'Partial',            'color' => '#EAB308', 'position' => 13, 'is_final' => false],
            ['slug' => 'fulfilled',          'name' => 'Fulfilled',          'color' => '#059669', 'position' => 14, 'is_final' => true],
        ];

        foreach ($canonicalStatuses as $status) {
            DB::table('shipment_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                array_merge($status, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $legacyFailureId = DB::table('shipment_statuses')->where('slug', 'failure')->value('id');
        $deliveryFailedId = DB::table('shipment_statuses')->where('slug', 'delivery_failed')->value('id');

        if ($legacyFailureId && $deliveryFailedId) {
            DB::table('shipments')
                ->where('shipment_status_id', $legacyFailureId)
                ->update(['shipment_status_id' => $deliveryFailedId]);

            DB::table('shipment_statuses')->where('slug', 'failure')->delete();
        }

        DB::table('shipment_statuses')->where('slug', 'ready_for_pickup')->delete();
        DB::table('shipment_statuses')->where('slug', 'picked_up')->delete();

        DB::table('shipment_statuses')->where('slug', 'failure')->delete();
    }

    public function down(): void
    {
        $legacyStatuses = [
            ['slug' => 'label_created',    'name' => 'Label Created',    'color' => '#9CA3AF', 'position' => 1, 'is_final' => false],
            ['slug' => 'ready_for_pickup', 'name' => 'Ready for Pickup', 'color' => '#60A5FA', 'position' => 2, 'is_final' => false],
            ['slug' => 'picked_up',        'name' => 'Picked Up',        'color' => '#3B82F6', 'position' => 3, 'is_final' => false],
            ['slug' => 'out_for_delivery', 'name' => 'Out for Delivery', 'color' => '#F59E0B', 'position' => 4, 'is_final' => false],
            ['slug' => 'delivered',        'name' => 'Delivered',        'color' => '#10B981', 'position' => 5, 'is_final' => true],
            ['slug' => 'delayed',          'name' => 'Delayed',          'color' => '#F97316', 'position' => 6, 'is_final' => false],
            ['slug' => 'failure',          'name' => 'Failure',          'color' => '#EF4444', 'position' => 7, 'is_final' => true],
            ['slug' => 'returned',         'name' => 'Returned',         'color' => '#8B5CF6', 'position' => 8, 'is_final' => true],
        ];

        foreach ($legacyStatuses as $status) {
            DB::table('shipment_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                array_merge($status, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $deliveryFailedId = DB::table('shipment_statuses')->where('slug', 'delivery_failed')->value('id');
        $failureId = DB::table('shipment_statuses')->where('slug', 'failure')->value('id');

        if ($deliveryFailedId && $failureId) {
            DB::table('shipments')
                ->where('shipment_status_id', $deliveryFailedId)
                ->update(['shipment_status_id' => $failureId]);
        }

        $newSlugs = [
            'unfulfilled', 'label_purchased', 'label_printed', 'confirmed',
            'in_transit', 'attempted_delivery', 'partial', 'fulfilled',
        ];

        DB::table('shipment_statuses')->whereIn('slug', $newSlugs)->delete();
    }
};
