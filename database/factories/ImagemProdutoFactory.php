<?php

namespace Database\Factories;

use App\Models\Produto;
use App\Models\ImagemProduto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImagemProduto>
 */
class ImagemProdutoFactory extends Factory
{
    protected $model = ImagemProduto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Produto::factory(),
            'path' => 'products/'.fake()->uuid().'.jpg',
            'position' => 0,
        ];
    }
}
