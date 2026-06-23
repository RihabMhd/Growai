<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_companies', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_companies', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
        });

        $this->seedShipmentStatuses();

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('shipment_status_id')
                ->nullable()
                ->after('tracking_number')
                ->constrained('shipment_statuses')
                ->nullOnDelete();
        });

        $statusMap = DB::table('shipment_statuses')->pluck('id', 'slug');

        $legacyMap = [
            'pending' => 'label_created',
            'picked_up' => 'picked_up',
            'in_transit' => 'out_for_delivery',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'returned' => 'returned',
            'failed' => 'failure',
        ];

        DB::table('shipments')->orderBy('id')->get()->each(function ($shipment) use ($statusMap, $legacyMap) {
            $slug = $legacyMap[$shipment->status] ?? 'label_created';
            $statusId = $statusMap[$slug] ?? $statusMap['label_created'];

            DB::table('shipments')
                ->where('id', $shipment->id)
                ->update(['shipment_status_id' => $statusId]);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'picked_up',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'returned',
                'failed',
            ])->default('pending')->after('tracking_number');
        });

        $statusMap = DB::table('shipment_statuses')->pluck('slug', 'id');

        DB::table('shipments')->orderBy('id')->get()->each(function ($shipment) use ($statusMap) {
            $slug = $statusMap[$shipment->shipment_status_id] ?? 'label_created';

            $reverseMap = [
                'label_created' => 'pending',
                'ready_for_pickup' => 'pending',
                'picked_up' => 'picked_up',
                'out_for_delivery' => 'out_for_delivery',
                'delivered' => 'delivered',
                'delayed' => 'in_transit',
                'failure' => 'failed',
                'returned' => 'returned',
            ];

            DB::table('shipments')
                ->where('id', $shipment->id)
                ->update(['status' => $reverseMap[$slug] ?? 'pending']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipment_status_id');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_companies', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }

    private function seedShipmentStatuses(): void
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
};
