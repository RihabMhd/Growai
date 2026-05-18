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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('shop_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Basic Information
            $table->string('title');
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('handle')->unique(); // slug
            $table->enum('status', ['active', 'draft', 'archived'])->default('draft');
            
            // Tags as JSON
            $table->json('tags')->nullable();
            
            // Images
            $table->string('image')->nullable();
            $table->json('images')->nullable(); // Multiple images
            
            // Description
            $table->text('description')->nullable();
            
            // Variants (stored as JSON for flexibility)
            $table->json('variants')->nullable();
            
            // External sync
            $table->string('external_product_id')->nullable();
            $table->enum('source_type', ['shopify', 'manual'])->default('manual');
            
            $table->timestamps();
            
            // Indexes
            $table->index('handle');
            $table->index('status');
            $table->index('vendor');
            $table->index('product_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};