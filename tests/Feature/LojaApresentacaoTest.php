<?php

namespace Tests\Feature;

use App\Cart\CarrinhoSessao;
use App\Models\ImagemProduto;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LojaApresentacaoTest extends TestCase
{
    use RefreshDatabase;

    private function produto(array $overrides = [], array $paths = ['products/cover.png']): Produto
    {
        Storage::fake('public');

        $produto = Produto::factory()->create(array_merge([
            'nome' => 'Bolsa Alerta',
            'sku' => 'ALERT-1',
            'preco' => '49.90',
            'quantidade' => 15,
            'cores' => ['Vermelho', 'Amarelo'],
        ], $overrides));

        foreach (array_values($paths) as $position => $path) {
            Storage::disk('public')->put($path, 'img-'.$position);
            ImagemProduto::factory()->create([
                'produto_id' => $produto->id,
                'caminho' => $path,
                'posicao' => $position,
            ]);
        }

        return $produto->refresh()->load('imagens');
    }

    public function test_aviso_de_sucesso_aparece_uma_vez_apos_adicionar(): void
    {
        $produto = $this->produto();

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'produto_id' => $produto->id,
            'cor' => 'Vermelho',
            'quantidade' => 1,
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
        $produto = $this->produto(['quantidade' => 5]);

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'produto_id' => $produto->id,
            'cor' => 'Vermelho',
            'quantidade' => 3,
        ]);
        $this->post(route('loja.carrinho.itens.adicionar'), [
            'produto_id' => $produto->id,
            'cor' => 'Amarelo',
            'quantidade' => 1,
        ]);

        $redId = session(CarrinhoSessao::CHAVE_SESSAO)[0]['id'];
        $yellowId = session(CarrinhoSessao::CHAVE_SESSAO)[1]['id'];

        $response = $this->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $redId), ['quantidade' => 5]);

        $response->assertSessionHasErrors('itens_carrinho.'.$redId)
            ->assertSessionDoesntHaveErrors('itens_carrinho.'.$yellowId)
            ->assertSessionDoesntHaveErrors('quantidade');

        $this->followingRedirects()
            ->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $redId), ['quantidade' => 5])
            ->assertSee('A quantidade pedida ultrapassa o estoque disponível.')
            ->assertSee('id="formulario-qtd-carrinho-'.$redId.'"', false)
            ->assertSee('id="formulario-qtd-carrinho-'.$yellowId.'"', false);
    }

    public function test_imagem_ausente_usa_reserva_sem_onerror_recursivo(): void
    {
        Storage::fake('public');
        $produto = Produto::factory()->create(['nome' => 'Capa quebrada', 'sku' => 'BRK-1']);
        ImagemProduto::factory()->create([
            'produto_id' => $produto->id,
            'caminho' => 'products/missing.png',
            'posicao' => 0,
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
        $produto = $this->produto([], ['products/a.jpg', 'products/b.jpg']);

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertOk()
            ->assertSee('foto-produto__img', false)
            ->assertSee('this.onerror=null', false)
            ->assertSee('Miniatura 2')
            ->assertSee('slider-navFive', false);
    }
}
