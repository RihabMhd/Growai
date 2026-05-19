<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            // e.g. { "status": "abandoned", "hours_since": 2 }
            $table->json('trigger_condition')->nullable();

            $table->string('action', 50);
            // send_whatsapp | send_sms | send_email | assign_agent

            $table->integer('delay_minutes')->default(0);

            // Template message to send
            $table->text('message_template')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_rules');
    }
};
