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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp', 30)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('remember_token');
            }
        });

        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'order_prefix')) {
                $table->string('order_prefix', 10)->default('ORD')->after('id');
            }
            if (!Schema::hasColumn('teams', 'country')) {
                $table->string('country', 5)->default('MA')->after('order_prefix');
            }
            if (!Schema::hasColumn('teams', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 2)->default(10.00)->after('country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'two_factor_enabled']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['order_prefix', 'country', 'exchange_rate']);
        });
    }
};
