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
        Schema::table('shops', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('is_active');
            $table->string('webhook_secret')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['shopify_domain', 'access_token', 'is_active', 'last_synced_at', 'webhook_secret']);
        });
    }
};
