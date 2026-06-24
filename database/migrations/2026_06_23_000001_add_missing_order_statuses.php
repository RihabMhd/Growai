<?php

use App\Domain\Orders\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const MISSING_STATUSES = [
        ['slug' => 'nouveau',       'name' => 'Nouveau'],
        ['slug' => 'no_response',   'name' => 'Pas de réponse'],
        ['slug' => 'rappel',        'name' => 'Rappel'],
        ['slug' => 'doublon',       'name' => 'Doublon'],
        ['slug' => 'wrong_number',  'name' => 'Mauvais numéro'],
        ['slug' => 'processing',    'name' => 'En traitement'],
        ['slug' => 'shipped',       'name' => 'Expédié'],
    ];

    public function up(): void
    {
        foreach (self::MISSING_STATUSES as $status) {
            OrderStatus::firstOrCreate(
                ['slug' => $status['slug']],
                $status
            );
        }
    }

    public function down(): void
    {
        OrderStatus::whereIn('slug', array_column(self::MISSING_STATUSES, 'slug'))->delete();
    }
};
