<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();   // e.g. nouveau, confirme, livre, annule
            $table->string('name');             // Display name (FR/AR/EN)
            $table->string('color', 20)->default('#6b7280'); // hex badge color

            // WhatsApp auto-message
            $table->text('whatsapp_message')->nullable();
            $table->boolean('auto_send')->default(false);
            $table->json('templates')->nullable(); // multilingual templates

            // Ordering in Kanban / pipeline
            $table->integer('position')->default(0);

            $table->boolean('is_final')->default(false); // livré, annulé...

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
