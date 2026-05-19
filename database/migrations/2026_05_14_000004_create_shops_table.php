<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->string('platform', 30)->default('shopify');
            // shopify | tiktok | meta | manual

            $table->string('shopify_domain')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
