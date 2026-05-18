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
        // 1. Add wallet balance to users
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 12, 2)->default(0.00)->after('commission_type');
        });

        // 2. Add assignment details and commission state to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('is_abandoned')
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('commission_paid')->default(false)->after('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wallet_balance');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['assigned_to', 'commission_paid']);
        });
    }
};
