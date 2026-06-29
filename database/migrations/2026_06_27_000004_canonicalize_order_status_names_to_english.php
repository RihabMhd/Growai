<?php

declare(strict_types=1);

use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Slug => English name mapping.
     */
    private const NAME_MAP = [
        'new' => 'New',
        'confirmed' => 'Confirmed',
        'no_response' => 'No Response',
        'callback' => 'Callback',
        'cancelled' => 'Cancelled',
        'duplicate' => 'Duplicate',
        'wrong_number' => 'Wrong Number',
    ];

    public function up(): void
    {
        // Update only the canonical English slugs we care about.
        DB::transaction(function () {
            foreach (self::NAME_MAP as $slug => $englishName) {
                // Use query builder to avoid model observers.
                DB::table('order_statuses')
                    ->where('slug', $slug)
                    ->update(['name' => $englishName]);
            }
        });
    }

    public function down(): void
    {
        // Intentionally not implemented: the backend must remain English-only.
    }
};

