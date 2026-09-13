<?php

namespace Database\Factories;

use App\Models\ImagemProduto;
use App\Models\Produto;
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
            'produto_id' => Produto::factory(),
            'caminho' => 'products/'.fake()->uuid().'.jpg',
            'posicao' => 0,
        ];
    }
}
