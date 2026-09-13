<?php

namespace Tests\Feature;

use App\Cart\CarrinhoSessao;
use App\Models\Produto;
use App\Models\ImagemProduto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LojaApresentacaoTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = [], array $paths = ['products/cover.png']): Produto
    {
        Storage::fake('public');

        $produto = Produto::factory()->create(array_merge([
            'name' => 'Alert Bag',
            'sku' => 'ALERT-1',
            'price' => '49.90',
            'qty' => 15,
            'colors' => ['Red', 'Yellow'],
        ], $overrides));

        foreach (array_values($paths) as $position => $path) {
            Storage::disk('public')->put($path, 'img-'.$position);
            ImagemProduto::factory()->create([
                'product_id' => $produto->id,
                'path' => $path,
                'position' => $position,
            ]);
        }

        return $produto->refresh()->load('imagens');
    }

    public function test_aviso_de_sucesso_aparece_uma_vez_apos_adicionar(): void
    {
        $produto = $this->product();

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'product_id' => $produto->id,
            'color' => 'Red',
            'quantity' => 1,
        ])->assertRedirect(route('carrinho'));

        $this->get(route('carrinho'))
            ->assertSee('O produto foi adicionado ao carrinho.')
            ->assertSee('alert-success', false)
            ->assertSee('Fechar', false);

        $this->get(route('carrinho'))
            ->assertDontSee(__('The product was added to the cart.'));
    }

    public function test_erro_de_quantidade_fica_na_linha_afetada(): void
    {
        $produto = $this->product(['qty' => 5]);

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'product_id' => $produto->id,
            'color' => 'Red',
            'quantity' => 3,
        ]);
        $this->post(route('loja.carrinho.itens.adicionar'), [
            'product_id' => $produto->id,
            'color' => 'Yellow',
            'quantity' => 1,
        ]);

        $redId = session(CarrinhoSessao::CHAVE_SESSAO)[0]['id'];
        $yellowId = session(CarrinhoSessao::CHAVE_SESSAO)[1]['id'];

        $response = $this->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $redId), ['quantity' => 5]);

        $response->assertSessionHasErrors('cart_items.'.$redId)
            ->assertSessionDoesntHaveErrors('cart_items.'.$yellowId)
            ->assertSessionDoesntHaveErrors('quantity');

        $this->followingRedirects()
            ->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $redId), ['quantity' => 5])
            ->assertSee('A quantidade pedida ultrapassa o estoque disponível.')
            ->assertSee('id="cart-qty-form-'.$redId.'"', false)
            ->assertSee('id="cart-qty-form-'.$yellowId.'"', false);
    }

    public function test_imagem_ausente_usa_reserva_sem_onerror_recursivo(): void
    {
        Storage::fake('public');
        $produto = Produto::factory()->create(['name' => 'Broken Cover', 'sku' => 'BRK-1']);
        ImagemProduto::factory()->create([
            'product_id' => $produto->id,
            'path' => 'products/missing.png',
            'position' => 0,
        ]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Sem capa')
            ->assertDontSee('products/missing.png', false);

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertOk()
            ->assertSee('Sem capa')
            ->assertDontSee('this.onerror=null', false);
    }

    public function test_galeria_renderiza_fotos_com_contain_para_varias_imagens(): void
    {
        $produto = $this->product([], ['products/a.jpg', 'products/b.jpg']);

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertOk()
            ->assertSee('store-photo__img', false)
            ->assertSee('this.onerror=null', false)
            ->assertSee('Miniatura 2')
            ->assertSee('slider-navFive', false);
    }
}
