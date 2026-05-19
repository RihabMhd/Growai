<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Channel origin
            $table->string('type', 30);
            // shopify | facebook | tiktok | instagram | whatsapp | manual

            $table->string('platform', 30)->nullable();
            $table->string('external_id')->nullable();

            // Ad tracking
            $table->string('campaign_name')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('adset_name')->nullable();
            $table->string('ad_id')->nullable();

            // UTM
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sources');
    }
};
