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
        Schema::create('recovery_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('whatsapp_templates')
                ->nullOnDelete();

            $table->string('name');

            $table->json('trigger_condition')->nullable();

            $table->string('action');

            $table->integer('delay_minutes')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_rules');
    }
};
