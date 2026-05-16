<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro Max',
                'sku' => 'IPHONE15PM',
                'price' => 1299.00,
                'stock' => 50,
                'source_type' => 'manual',
                'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=150'
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'sku' => 'SAMS24U',
                'price' => 1199.00,
                'stock' => 40,
                'source_type' => 'manual',
                'image' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=150'
            ],
            [
                'name' => 'MacBook Pro M3 Max',
                'sku' => 'MACBPRO-M3',
                'price' => 2499.00,
                'stock' => 15,
                'source_type' => 'manual',
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=150'
            ],
            [
                'name' => 'Sony WH-1000XM5 ANC',
                'sku' => 'SONYXM5',
                'price' => 349.00,
                'stock' => 60,
                'source_type' => 'manual',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=150'
            ],
            [
                'name' => 'iPad Pro M4 Ultra',
                'sku' => 'IPADPM4',
                'price' => 999.00,
                'stock' => 25,
                'source_type' => 'manual',
                'image' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=150'
            ],
            [
                'name' => 'Apple Watch Ultra 2',
                'sku' => 'AWATCH-ULTRA2',
                'price' => 799.00,
                'stock' => 30,
                'source_type' => 'manual',
                'image' => 'https://images.unsplash.com/photo-1434494878577-86c23bcb06b9?w=150'
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
