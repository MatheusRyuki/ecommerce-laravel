<?php

namespace Database\Factories;

use App\Models\Product;
use App\Support\ProductColors;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'price' => '19.90',
            'colors' => [ProductColors::all()[0]],
            'short_description' => fake()->sentence(),
            'qty' => 10,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'description' => '<p>'.fake()->sentence().'</p>',
        ];
    }
}
