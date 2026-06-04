<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = \App\Domain\Products\Models\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->words(3, true);
        return [
            'title' => $title,
            'handle' => \Illuminate\Support\Str::slug($title),
            'vendor' => $this->faker->company(),
            'product_type' => 'Gadget',
            'status' => 'active',
            'source_type' => 'manual',
            'variants' => [
                [
                    'title' => 'Default Title',
                    'sku' => strtoupper($this->faker->unique()->lexify('???-???')),
                    'price' => $this->faker->randomFloat(2, 10, 1000),
                    'stock' => $this->faker->numberBetween(0, 100),
                ]
            ]
        ];
    }
}
