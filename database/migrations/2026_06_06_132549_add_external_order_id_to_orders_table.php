<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('external_order_id')->nullable()->after('shop_id');

            // customer info
            $table->string('customer_name')->nullable()->after('notes');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone')->nullable()->after('customer_email');

            // fulfillment
            $table->string('fulfillment_status')->nullable()->after('financial_status');

            // shipping address + order date
            $table->json('shipping_address')->nullable()->after('customer_phone');
            $table->timestamp('order_date')->nullable()->after('shipping_address');

            $table->unique(['shop_id', 'external_order_id'], 'orders_shop_external_unique');
        });

        // Drop old global unique on order_number
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropUnique(['order_number']);
            } catch (\Exception $e) {
                // already gone
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'external_order_id',
                'customer_name',
                'customer_email',
                'customer_phone',
                'fulfillment_status',
                'shipping_address',
                'order_date',
            ]);
            try {
                $table->dropUnique('orders_shop_external_unique');
            } catch (\Exception $e) {
            }
            try {
                $table->unique('order_number');
            } catch (\Exception $e) {
            }
        });
    }
};
