<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();

            // Dispatch & commission settings
            $table->boolean('dispatch_auto')->default(false);
            $table->string('inactive_strategy', 50)->default('do_nothing');
            $table->string('commission_currency', 20)->default('MAD');

            // WhatsApp language
            $table->string('whatsapp_language', 10)->default('FR');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
