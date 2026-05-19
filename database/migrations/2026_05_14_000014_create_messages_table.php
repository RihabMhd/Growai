<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('channel', [
                'whatsapp',
                'call',
                'sms',
                'email',
            ])->index();

            $table->enum('direction', [
                'incoming',
                'outgoing',
            ])->default('outgoing');

            $table->text('message');

            $table->enum('status', [
                'pending',
                'sent',
                'delivered',
                'read',
                'failed',
            ])->default('pending');

            $table->string('external_message_id')->nullable(); // WhatsApp message ID
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
