<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('clients', function (Blueprint $table) {
            $table->string('province')->nullable()->after('city');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('source_channel', 30)->nullable()->default(null)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('province');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('source_channel');
        });
    }
};