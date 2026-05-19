<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('delivery_company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('tracking_number')->nullable()->index();

            $table->enum('status', [
                'pending',
                'picked_up',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'returned',
                'failed',
            ])->default('pending')->index();

            // Destination snapshot
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country', 5)->default('MA');

            // COD (Cash on Delivery)
            $table->decimal('cod_amount', 10, 2)->default(0);

            $table->text('delivery_notes')->nullable();

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
