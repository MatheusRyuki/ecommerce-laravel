<?php

namespace Database\Factories;

use App\Models\Produto;
use App\Support\CoresProduto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produto>
 */
class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'price' => '19.90',
            'colors' => [CoresProduto::todas()[0]],
            'short_description' => fake()->sentence(),
            'qty' => 10,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'description' => '<p>'.fake()->sentence().'</p>',
        ];
    }
}
