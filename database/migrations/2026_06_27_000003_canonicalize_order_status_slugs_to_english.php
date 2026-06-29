<?php

declare(strict_types=1);

use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Canonical English slug mapping.
     */
    private const SLUG_MAP = [
        'nouveau'    => 'new',
        'no_answer'  => 'no_response',
        'rappel'     => 'callback',
        'doublon'    => 'duplicate',
        'wrong_num'  => 'wrong_number',
    ];

    public function up(): void
    {
        // 1) Migrate orders.status
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            DB::table('orders')
                ->where('status', $oldSlug)
                ->update(['status' => $newSlug]);
        }

        // 2) Migrate order histories old_value/new_value
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            DB::table('order_histories')
                ->where('new_value', $oldSlug)
                ->update(['new_value' => $newSlug]);

            DB::table('order_histories')
                ->where('old_value', $oldSlug)
                ->update(['old_value' => $newSlug]);
        }

        // 3) Ensure order_statuses records exist for canonical slugs
        //    and then migrate delete obsolete slugs.
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            if (! OrderStatus::where('slug', $newSlug)->exists()) {
                // Create canonical records with minimal defaults.
                // Names are not critical for integrity; frontend translations rely on slugs.
                OrderStatus::create([
                    'slug' => $newSlug,
                    'name' => $newSlug,
                    'auto_send' => false,
                    'whatsapp_message' => null,
                    'templates' => json_encode([]),
                ]);
            }
        }

        // 4) Migrate order_statuses.slug (aliases removed)
        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            OrderStatus::where('slug', $oldSlug)->delete();
        }
    }

    public function down(): void
    {
        // Intentionally not implemented: canonical slugs should remain stable.
    }
};

