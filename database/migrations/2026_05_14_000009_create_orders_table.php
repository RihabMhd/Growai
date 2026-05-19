<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shop_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            // Staff assignment
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('order_number')->unique();

            // Amounts
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('shipping_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('currency', 10)->default('MAD');

            // Status (references order_statuses.slug for flexibility)
            $table->string('status')->default('nouveau')->index();

            $table->enum('financial_status', [
                'unpaid',
                'pending',
                'paid',
                'refunded',
            ])->default('unpaid');

            // Commission
            $table->boolean('commission_paid')->default(false);

            // Abandoned cart
            $table->boolean('is_abandoned')->default(false);
            $table->timestamp('abandoned_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('financial_status');
            $table->index('is_abandoned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
