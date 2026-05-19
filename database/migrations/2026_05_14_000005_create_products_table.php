<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shop_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Basic information
            $table->string('title');
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('handle')->unique(); // URL slug

            $table->enum('status', ['active', 'draft', 'archived'])->default('draft');

            // Content
            $table->text('description')->nullable();
            $table->json('tags')->nullable();

            // Images
            $table->string('image')->nullable();
            $table->json('images')->nullable();

            // Variants (JSON for flexibility: title, sku, price, stock, cost, compare_at_price)
            $table->json('variants')->nullable();

            // Global cost (when no variants)
            $table->decimal('cost', 10, 2)->nullable();

            // External sync
            $table->string('external_product_id')->nullable()->index();
            $table->enum('source_type', ['shopify', 'tiktok', 'meta', 'manual'])->default('manual');
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('vendor');
            $table->index('product_type');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
