<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Carrier identifiers — keep generic (no AMEEX-specific columns)
            $table->string('parcel_code')->nullable()->index();
            $table->string('external_reference')->nullable()->index();
            $table->string('carrier_tracking_number')->nullable()->index();

            // Store full carrier payload used to derive identifiers/status (optional but helpful)
            $table->json('carrier_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'parcel_code',
                'external_reference',
                'carrier_tracking_number',
                'carrier_payload',
            ]);
        });
    }
};
