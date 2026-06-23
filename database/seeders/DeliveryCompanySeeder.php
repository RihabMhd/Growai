<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['name' => 'Ameex', 'slug' => 'ameex', 'api_url' => 'https://api.ameex.ma'],
            ['name' => 'Cathedis', 'slug' => 'cathedis', 'api_url' => 'https://api.cathedis.ma'],
            ['name' => 'Ozon', 'slug' => 'ozon', 'api_url' => 'https://api.ozonexpress.ma'],
            ['name' => 'Chrono Diali', 'slug' => 'chrono_diali', 'api_url' => 'https://api.chronodiali.ma'],
            ['name' => 'Generic Carrier', 'slug' => 'generic', 'api_url' => null],
        ];

        foreach ($companies as $company) {
            DB::table('delivery_companies')->updateOrInsert(
                ['slug' => $company['slug']],
                array_merge($company, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
