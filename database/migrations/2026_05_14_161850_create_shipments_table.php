<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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

            $table->string('tracking_number')->nullable();
            
            $table->enum('status', [
                'pending',
                'in_transit',
                'delivered',
                'failed'
            ])->default('pending');

            // Consolidated Shipping Destination Data (Like Temu)
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('region')->nullable();

            // Financial & Logistics specifics
            $table->decimal('cod_amount', 10, 2)->default(0);
            $table->text('delivery_notes')->nullable();

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
