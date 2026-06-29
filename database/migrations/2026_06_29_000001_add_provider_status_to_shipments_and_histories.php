<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('provider_status')->nullable()->after('shipment_status_id');
        });

        Schema::table('shipment_histories', function (Blueprint $table) {
            $table->string('provider_status')->nullable()->after('new_status');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('provider_status');
        });

        Schema::table('shipment_histories', function (Blueprint $table) {
            $table->dropColumn('provider_status');
        });
    }
};
