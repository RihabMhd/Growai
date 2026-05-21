<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            // ── Confirmation statuses ─────────────────────────────────────────
            ['slug' => 'pending',            'name' => 'Nouveau',            'auto_send' => false],
            ['slug' => 'confirmed',          'name' => 'Confirmé',           'auto_send' => false],
            ['slug' => 'no_answer',          'name' => 'Pas de réponse',     'auto_send' => false],
            ['slug' => 'callback',           'name' => 'Rappel',             'auto_send' => false],
            ['slug' => 'cancelled',          'name' => 'Annulé',             'auto_send' => false],
            ['slug' => 'duplicate',          'name' => 'Doublon',            'auto_send' => false],
            ['slug' => 'wrong_num',          'name' => 'Mauvais numéro',     'auto_send' => false],

            // ── Company / delivery statuses ───────────────────────────────────
            ['slug' => 'label_created',      'name' => 'Label Created',      'auto_send' => false],
            ['slug' => 'ready_for_pickup',   'name' => 'Ready for Pickup',   'auto_send' => false],
            ['slug' => 'out_for_delivery',   'name' => 'Out for Delivery',   'auto_send' => false],
            ['slug' => 'attempted_delivery', 'name' => 'Attempted Delivery', 'auto_send' => false],
            ['slug' => 'picked_up',          'name' => 'Picked Up',          'auto_send' => false],
            ['slug' => 'delivered',          'name' => 'Delivered',          'auto_send' => false],
            ['slug' => 'delayed',            'name' => 'Delayed',            'auto_send' => false],
            ['slug' => 'returned',           'name' => 'Returned',           'auto_send' => false],
        ];

        foreach ($statuses as $status) {
            OrderStatus::firstOrCreate(
                ['slug' => $status['slug']],
                $status
            );
        }
    }
}