<?php

use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping of known corrupted database values → proper UTF-8 values.
     * Only rows that actually contain the corrupted text are updated.
     */
    private const REPAIRS = [
        // Old seeder had mojibake due to wrong file encoding.
        'AbandonnÃ©' => 'Abandonné',
        'RÃ©cupÃ©rÃ©' => 'Récupéré',
    ];

    public function up(): void
    {
        foreach (self::REPAIRS as $bad => $good) {
            $count = OrderStatus::where('name', $bad)->count();

            if ($count > 0) {
                DB::table('order_statuses')
                    ->where('name', $bad)
                    ->update(['name' => $good]);

                // Also repair any order_histories that may have captured
                // the corrupted value at changeover time.
                DB::table('order_histories')
                    ->where('new_value', $bad)
                    ->update(['new_value' => $good]);
                DB::table('order_histories')
                    ->where('old_value', $bad)
                    ->update(['old_value' => $good]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally not reversible — corrupted values should not be
        // restored. The canonical UTF-8 values are the source of truth.
    }
};
