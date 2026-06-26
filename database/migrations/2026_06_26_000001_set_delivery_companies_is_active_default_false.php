<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure new delivery_companies records default to inactive
        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->change();
        });

    }

    public function down(): void
    {
        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
        });
    }
};
