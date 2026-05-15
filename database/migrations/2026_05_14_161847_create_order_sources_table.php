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
        Schema::create('order_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type');
            // shopify, facebook, tiktok, whatsapp, manual

            $table->string('platform')->nullable();

            $table->string('external_id')->nullable();

            $table->string('campaign_name')->nullable();
            $table->string('campaign_id')->nullable();

            $table->string('adset_name')->nullable();
            $table->string('ad_id')->nullable();

            $table->string('utm_source')->nullable();
            $table->string('utm_campaign')->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_sources');
    }
};
