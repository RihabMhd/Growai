<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_company_id')->constrained()->cascadeOnDelete();
            $table->string('event', 100)->nullable();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_webhook_logs');
    }
};
