<?php

namespace Tests\Feature;

use App\Cart\CarrinhoSessao;
use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LojaCarrinhoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $colors
     */
    private function produto(array $overrides = [], array $colors = ['Vermelho', 'Amarelo'], array $paths = ['products/cart-cover.png']): Produto
    {
        Storage::fake('public');

        $produto = Produto::factory()->create(array_merge([
            'nome' => 'Bolsa Carrinho',
            'sku' => 'CART-0001',
            'preco' => '49.90',
            'quantidade' => 15,
            'cores' => $colors,
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function addItem(Produto $produto, array $overrides = []): TestResponse
    {
        return $this->from(route('loja.produtos.exibir', $produto))
            ->post(route('loja.carrinho.itens.adicionar'), array_merge([
                'produto_id' => $produto->id,
                'cor' => 'Vermelho',
                'quantidade' => 1,
            ], $overrides));
    }

    public function test_carrinho_vazio_e_publico(): void
    {
        $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Seu carrinho está vazio.')
            ->assertSee('Continuar comprando')
            ->assertSee(route('inicio'), false);
    }

    public function test_carrinho_legado_na_sessao_continua_legivel(): void
    {
        $produto = $this->produto();

        $this->withSession([
            CarrinhoSessao::CHAVE_SESSAO_LEGADA => [[
                'id' => 'linha-legado',
                'product_id' => $produto->id,
                'color' => 'Red',
                'quantity' => 2,
            ]],
        ])->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Bolsa Carrinho')
            ->assertSee('Vermelho')
            ->assertSee('R$ 99,80');
    }

    public function test_adicao_valida_persiste_entre_requisicoes(): void
    {
        $produto = $this->produto();

        $this->addItem($produto, ['quantidade' => 2])
            ->assertRedirect(route('carrinho'))
            ->assertSessionHas('status');

        $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Bolsa Carrinho')
            ->assertSee('Vermelho')
            ->assertSee('R$ 49,90')
            ->assertSee('value="2"', false)
            ->assertSee(route('loja.produtos.exibir', $produto), false)
            ->assertSee(Storage::disk('public')->url('products/cart-cover.png'), false)
            ->assertSee('O produto foi adicionado ao carrinho.');

        $this->get(route('carrinho'))->assertSee('Bolsa Carrinho')->assertSee('>2</b>', false);
    }

    public function test_mesma_combinacao_acumula_e_cores_viram_linhas_separadas(): void
    {
        $produto = $this->produto();

        $this->addItem($produto, ['cor' => 'Vermelho', 'quantidade' => 2]);
        $this->addItem($produto, ['cor' => 'Vermelho', 'quantidade' => 1]);
        $this->addItem($produto, ['cor' => 'Amarelo', 'quantidade' => 1]);

        $items = session(CarrinhoSessao::CHAVE_SESSAO);
        $this->assertCount(2, $items);
        $this->assertSame(3, $items[0]['quantidade']);
        $this->assertSame('Vermelho', $items[0]['cor']);
        $this->assertSame(1, $items[1]['quantidade']);
        $this->assertSame('Amarelo', $items[1]['cor']);
        $this->assertNotSame($items[0]['id'], $items[1]['id']);

        $this->get(route('carrinho'))
            ->assertSee('Vermelho')
            ->assertSee('Amarelo')
            ->assertSee('>4</b>', false);
    }

    public function test_limite_de_estoque_considera_todas_as_cores(): void
    {
        $produto = $this->produto(['quantidade' => 5]);

        $this->addItem($produto, ['cor' => 'Vermelho', 'quantidade' => 3])->assertRedirect(route('carrinho'));
        $this->addItem($produto, ['cor' => 'Amarelo', 'quantidade' => 3])
            ->assertRedirect(route('loja.produtos.exibir', $produto))
            ->assertSessionHasErrors('carrinho');

        $items = session(CarrinhoSessao::CHAVE_SESSAO);
        $this->assertCount(1, $items);
        $this->assertSame(3, $items[0]['quantidade']);
        $this->assertSame('Vermelho', $items[0]['cor']);
    }

    public function test_quantidade_cor_invalida_e_produto_ausente_sao_rejeitados(): void
    {
        $produto = $this->produto();

        $this->addItem($produto, ['quantidade' => 0])->assertSessionHasErrors('quantidade');
        $this->addItem($produto, ['quantidade' => 1.5])->assertSessionHasErrors('quantidade');
        $this->addItem($produto, ['cor' => 'Azul'])->assertSessionHasErrors('carrinho');
        $this->from(route('inicio'))
            ->post(route('loja.carrinho.itens.adicionar'), [
                'produto_id' => 99999,
                'cor' => 'Vermelho',
                'quantidade' => 1,
            ])
            ->assertSessionHasErrors('produto_id');

        $this->assertSame([], session(CarrinhoSessao::CHAVE_SESSAO, []));
    }

    public function test_adicao_rejeitada_preserva_carrinho_anterior(): void
    {
        $produto = $this->produto(['quantidade' => 4]);

        $this->addItem($produto, ['quantidade' => 2])->assertRedirect(route('carrinho'));
        $previous = session(CarrinhoSessao::CHAVE_SESSAO);

        $this->addItem($produto, ['quantidade' => 5])
            ->assertSessionHasErrors('carrinho');

        $this->assertSame($previous, session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_preco_enviado_pelo_cliente_nao_altera_preco_exibido(): void
    {
        $produto = $this->produto(['preco' => '49.90']);

        $this->addItem($produto, ['preco' => '0.01'])->assertRedirect(route('carrinho'));

        $this->get(route('carrinho'))
            ->assertSee('R$ 49,90')
            ->assertDontSee('R$ 0,01');
    }

    public function test_carrinhos_de_sessoes_independentes_permanecem_separados(): void
    {
        $first = $this->produto(['nome' => 'Bolsa sessao um', 'sku' => 'S1']);
        $second = $this->produto(['nome' => 'Bolsa sessao dois', 'sku' => 'S2'], ['Azul'], ['products/other.png']);

        $this->withSession([
            CarrinhoSessao::CHAVE_SESSAO => [[
                'id' => 'line-a',
                'produto_id' => $first->id,
                'cor' => 'Vermelho',
                'quantidade' => 1,
            ]],
        ])->get(route('carrinho'))
            ->assertSee('Bolsa sessao um')
            ->assertDontSee('Bolsa sessao dois');

        $this->withSession([
            CarrinhoSessao::CHAVE_SESSAO => [[
                'id' => 'line-b',
                'produto_id' => $second->id,
                'cor' => 'Azul',
                'quantidade' => 2,
            ]],
        ])->get(route('carrinho'))
            ->assertSee('Bolsa sessao dois')
            ->assertDontSee('Bolsa sessao um');
    }

    public function test_adicionar_nao_altera_estoque_do_produto(): void
    {
        $produto = $this->produto(['quantidade' => 15]);

        $this->addItem($produto, ['quantidade' => 3]);

        $this->assertSame(15, $produto->fresh()->quantidade);
    }

    public function test_get_nao_adiciona_itens(): void
    {
        $produto = $this->produto();

        $this->get('/carrinho/itens?produto_id='.$produto->id.'&cor=Vermelho&quantidade=1')
            ->assertMethodNotAllowed();

        $this->assertSame([], session(CarrinhoSessao::CHAVE_SESSAO, []));
    }

    public function test_usuario_autenticado_usa_o_mesmo_carrinho_da_sessao(): void
    {
        $produto = $this->produto();
        $user = Usuario::factory()->create();

        $this->actingAs($user)
            ->addItem($produto, ['quantidade' => 1])
            ->assertRedirect(route('carrinho'));

        $this->actingAs($user)
            ->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Bolsa Carrinho');
    }

    public function test_mudancas_no_catalogo_apos_adicionar_sao_sinalizadas(): void
    {
        $produto = $this->produto(['quantidade' => 10, 'preco' => '19.90', 'cores' => ['Vermelho', 'Amarelo']]);

        $this->addItem($produto, ['cor' => 'Vermelho', 'quantidade' => 4]);
        $this->addItem($produto, ['cor' => 'Amarelo', 'quantidade' => 2]);

        $produto->update([
            'quantidade' => 3,
            'cores' => ['Amarelo'],
            'preco' => '29.90',
        ]);

        $response = $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Bolsa Carrinho')
            ->assertSee('R$ 29,90')
            ->assertDontSee('R$ 19,90')
            ->assertSee('Este item precisa de ajuste')
            ->assertSee('value="4"', false)
            ->assertSee('value="2"', false);

        $this->assertCount(2, session(CarrinhoSessao::CHAVE_SESSAO));
        $this->assertSame(4, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantidade']);
        $this->assertSame(2, session(CarrinhoSessao::CHAVE_SESSAO)[1]['quantidade']);
        $this->assertTrue($response->baseResponse->isOk());

        $produto->update(['quantidade' => 0]);

        $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Indisponível')
            ->assertSee('value="4"', false);

        $produtoId = $produto->id;
        $produto->delete();

        $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Item indisponível')
            ->assertDontSee(route('loja.produtos.exibir', $produtoId), false)
            ->assertDontSee('R$ 29,90')
            ->assertSee('—');

        $this->assertCount(2, session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_adicionar_ao_carrinho_na_vitrine_aponta_para_detalhes(): void
    {
        $produto = $this->produto(['nome' => 'Vitrine Link Bag']);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee(route('loja.produtos.exibir', $produto), false)
            ->assertDontSee(route('loja.carrinho.itens.adicionar'), false);
    }

    public function test_detalhes_habilitam_adicionar_ao_carrinho_quando_disponivel(): void
    {
        $produto = $this->produto();

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertOk()
            ->assertSee(route('loja.carrinho.itens.adicionar'), false)
            ->assertSee('name="quantidade"', false)
            ->assertSee('name="cor"', false)
            ->assertSee('Selecione uma cor');
    }
}
