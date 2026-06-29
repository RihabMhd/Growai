<?php

use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Old slug → New slug mapping for duplicate/obsolete statuses.
     */
    private const SLUG_MAP = [
        'pending'    => 'nouveau',
        'no_answer'  => 'no_response',
        'rappel'     => 'callback',
        'doublon'    => 'duplicate',
        'wrong_num'  => 'wrong_number',
    ];

    public function up(): void
    {
        // 1. Migrate any orders that still reference the old slugs.
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            DB::table('orders')
                ->where('status', $oldSlug)
                ->update(['status' => $newSlug]);
        }

        // 2. Migrate order histories that reference the old slugs.
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            DB::table('order_histories')
                ->where('new_value', $oldSlug)
                ->update(['new_value' => $newSlug]);
            DB::table('order_histories')
                ->where('old_value', $oldSlug)
                ->update(['old_value' => $newSlug]);
        }

        // 3. Remove the obsolete status records (the ones whose slugs
        //    are being replaced by the canonical slug).
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            OrderStatus::where('slug', $oldSlug)->delete();
        }
    }

    public function down(): void
    {
        // Restore is intentionally not implemented because slug aliases
        // should not be re-introduced. The canonical slugs are:
        // nouveau, confirmed, no_response, callback, cancelled, duplicate,
        // wrong_number, abandoned, recovered, label_created, ready_for_pickup,
        // out_for_delivery, attempted_delivery, picked_up, delivered, delayed,
        // returned, processing, shipped.
    }
};
