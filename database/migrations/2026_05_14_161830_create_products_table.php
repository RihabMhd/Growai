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

            $table->string('external_product_id')->nullable();

            $table->string('name');
            $table->string('sku')->nullable();

            $table->decimal('price', 10, 2)->default(0);

            $table->integer('stock')->default(0);

            $table->enum('source_type', ['shopify', 'manual'])
                ->default('manual');

            $table->string('image')->nullable();

            $table->timestamps();
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
