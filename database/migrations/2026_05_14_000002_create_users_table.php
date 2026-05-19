<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable(); // nullable for social auth

            $table->enum('role', ['admin', 'staff'])->default('staff');

            $table->boolean('is_active')->default(true);

            // Staff dispatch & commission
            $table->integer('quota')->default(1);
            $table->boolean('is_dispatch_active')->default(true);
            $table->string('commission_trigger', 50)->default('none');
            // none | confirmed | delivered | paid
            $table->string('commission_type', 20)->default('fixed');
            // fixed | percentage
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('wallet_balance', 12, 2)->default(0.00);

            // Social auth (Google, Facebook, etc.)
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('avatar')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
