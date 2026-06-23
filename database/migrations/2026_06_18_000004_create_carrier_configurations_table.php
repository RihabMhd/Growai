<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_company_id')->constrained()->cascadeOnDelete();
            $table->json('credentials_json')->nullable();
            $table->json('field_mapping_json')->nullable();
            $table->boolean('auto_create_parcel')->default(false);
            $table->boolean('webhook_enabled')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'delivery_company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_configurations');
    }
};
