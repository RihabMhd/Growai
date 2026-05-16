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
        // Add settings to the teams table
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('dispatch_auto')->default(false)->after('id');
            $table->string('inactive_strategy')->default('do_nothing')->after('dispatch_auto');
            $table->string('commission_currency')->default('DZ DA')->after('inactive_strategy');
        });

        // Add settings to the users table
        Schema::table('users', function (Blueprint $table) {
            $table->integer('quota')->default(1)->after('is_active');
            $table->boolean('is_dispatch_active')->default(true)->after('quota');
            $table->string('commission_trigger')->default('none')->after('is_dispatch_active');
            $table->decimal('commission_amount', 10, 2)->default(0.00)->after('commission_trigger');
            $table->string('commission_type')->default('fixed')->after('commission_amount');
            
            // In case team_id constraints aren't loaded, ensure we can assign user to team
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['dispatch_auto', 'inactive_strategy', 'commission_currency']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'quota',
                'is_dispatch_active',
                'commission_trigger',
                'commission_amount',
                'commission_type'
            ]);
        });
    }
};
