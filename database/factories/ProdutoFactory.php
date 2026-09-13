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
            'nome' => fake()->words(3, true),
            'preco' => '19.90',
            'cores' => [CoresProduto::todas()[0]],
            'descricao_curta' => fake()->sentence(),
            'quantidade' => 10,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'publicado' => true,
            'categoria_id' => null,
            'descricao' => '<p>'.fake()->sentence().'</p>',
        ];
    }

    public function oculto(): static
    {
        return $this->state(fn (array $attributes): array => [
            'publicado' => false,
        ]);
    }
}
