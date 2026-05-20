<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipment_id')
                ->nullable()
                ->constrained('shipments')
                ->nullOnDelete()
                ->after('assigned_to');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_companies', 'credentials')) {
                $table->text('credentials')->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('delivery_companies', 'webhook_enabled')) {
                $table->boolean('webhook_enabled')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('delivery_companies', 'webhook_registered_at')) {
                $table->timestamp('webhook_registered_at')->nullable()->after('webhook_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeignIdFor('shipment_id');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->dropColumn(['credentials', 'webhook_enabled', 'webhook_registered_at']);
        });
    }
};
