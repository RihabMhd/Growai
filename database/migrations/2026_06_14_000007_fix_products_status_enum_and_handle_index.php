<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Widen the status enum to include 'deleted'.
        //    Laravel's ->change() does not support enum modification on all
        //    drivers, so we use a raw ALTER for MySQL / MariaDB compatibility.
        DB::statement(
            "ALTER TABLE products MODIFY COLUMN status
             ENUM('active','draft','archived','deleted') NOT NULL DEFAULT 'draft'"
        );

        // 2. Drop the global unique index on handle and replace it with a
        //    composite unique scoped to (shop_id, handle) so that multiple
        //    shops can each carry a product with the same handle slug without
        //    causing a constraint violation during upsert.
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->unique(['shop_id', 'handle'], 'products_shop_id_handle_unique');
        });
    }

    public function down(): void
    {
        // Restore the global unique index on handle.
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_shop_id_handle_unique');
            $table->unique(['handle']);
        });

        // Restore the narrower enum (removes rows with status='deleted' is the
        // caller's responsibility before rolling back in production).
        DB::statement(
            "ALTER TABLE products MODIFY COLUMN status
             ENUM('active','draft','archived') NOT NULL DEFAULT 'draft'"
        );
    }
};