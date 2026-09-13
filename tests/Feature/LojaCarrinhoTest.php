<?php

namespace Tests\Feature;

use App\Cart\CarrinhoSessao;
use App\Models\Produto;
use App\Models\ImagemProduto;
use App\Models\User;
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
    private function product(array $overrides = [], array $colors = ['Red', 'Yellow'], array $paths = ['products/cart-cover.png']): Produto
    {
        Storage::fake('public');

        $produto = Produto::factory()->create(array_merge([
            'name' => 'Cart Bag',
            'sku' => 'CART-0001',
            'price' => '49.90',
            'qty' => 15,
            'colors' => $colors,
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function addItem(Produto $produto, array $overrides = []): TestResponse
    {
        return $this->from(route('loja.produtos.exibir', $produto))
            ->post(route('loja.carrinho.itens.adicionar'), array_merge([
                'product_id' => $produto->id,
                'color' => 'Red',
                'quantity' => 1,
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

    public function test_adicao_valida_persiste_entre_requisicoes(): void
    {
        $produto = $this->product();

        $this->addItem($produto, ['quantity' => 2])
            ->assertRedirect(route('carrinho'))
            ->assertSessionHas('status');

        $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Cart Bag')
            ->assertSee('Vermelho')
            ->assertSee('R$ 49,90')
            ->assertSee('value="2"', false)
            ->assertSee(route('loja.produtos.exibir', $produto), false)
            ->assertSee(Storage::disk('public')->url('products/cart-cover.png'), false)
            ->assertSee('O produto foi adicionado ao carrinho.');

        $this->get(route('carrinho'))->assertSee('Cart Bag')->assertSee('>2</b>', false);
    }

    public function test_mesma_combinacao_acumula_e_cores_viram_linhas_separadas(): void
    {
        $produto = $this->product();

        $this->addItem($produto, ['color' => 'Red', 'quantity' => 2]);
        $this->addItem($produto, ['color' => 'Red', 'quantity' => 1]);
        $this->addItem($produto, ['color' => 'Yellow', 'quantity' => 1]);

        $items = session(CarrinhoSessao::CHAVE_SESSAO);
        $this->assertCount(2, $items);
        $this->assertSame(3, $items[0]['quantity']);
        $this->assertSame('Red', $items[0]['color']);
        $this->assertSame(1, $items[1]['quantity']);
        $this->assertSame('Yellow', $items[1]['color']);
        $this->assertNotSame($items[0]['id'], $items[1]['id']);

        $this->get(route('carrinho'))
            ->assertSee('Vermelho')
            ->assertSee('Amarelo')
            ->assertSee('>4</b>', false);
    }

    public function test_limite_de_estoque_considera_todas_as_cores(): void
    {
        $produto = $this->product(['qty' => 5]);

        $this->addItem($produto, ['color' => 'Red', 'quantity' => 3])->assertRedirect(route('carrinho'));
        $this->addItem($produto, ['color' => 'Yellow', 'quantity' => 3])
            ->assertRedirect(route('loja.produtos.exibir', $produto))
            ->assertSessionHasErrors('cart');

        $items = session(CarrinhoSessao::CHAVE_SESSAO);
        $this->assertCount(1, $items);
        $this->assertSame(3, $items[0]['quantity']);
        $this->assertSame('Red', $items[0]['color']);
    }

    public function test_quantidade_cor_invalida_e_produto_ausente_sao_rejeitados(): void
    {
        $produto = $this->product();

        $this->addItem($produto, ['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->addItem($produto, ['quantity' => 1.5])->assertSessionHasErrors('quantity');
        $this->addItem($produto, ['color' => 'Blue'])->assertSessionHasErrors('cart');
        $this->from(route('inicio'))
            ->post(route('loja.carrinho.itens.adicionar'), [
                'product_id' => 99999,
                'color' => 'Red',
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertSame([], session(CarrinhoSessao::CHAVE_SESSAO, []));
    }

    public function test_adicao_rejeitada_preserva_carrinho_anterior(): void
    {
        $produto = $this->product(['qty' => 4]);

        $this->addItem($produto, ['quantity' => 2])->assertRedirect(route('carrinho'));
        $previous = session(CarrinhoSessao::CHAVE_SESSAO);

        $this->addItem($produto, ['quantity' => 5])
            ->assertSessionHasErrors('cart');

        $this->assertSame($previous, session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_preco_enviado_pelo_cliente_nao_altera_preco_exibido(): void
    {
        $produto = $this->product(['price' => '49.90']);

        $this->addItem($produto, ['price' => '0.01'])->assertRedirect(route('carrinho'));

        $this->get(route('carrinho'))
            ->assertSee('R$ 49,90')
            ->assertDontSee('R$ 0,01');
    }

    public function test_carrinhos_de_sessoes_independentes_permanecem_separados(): void
    {
        $first = $this->product(['name' => 'Session One Bag', 'sku' => 'S1']);
        $second = $this->product(['name' => 'Session Two Bag', 'sku' => 'S2'], ['Blue'], ['products/other.png']);

        $this->withSession([
            CarrinhoSessao::CHAVE_SESSAO => [[
                'id' => 'line-a',
                'product_id' => $first->id,
                'color' => 'Red',
                'quantity' => 1,
            ]],
        ])->get(route('carrinho'))
            ->assertSee('Session One Bag')
            ->assertDontSee('Session Two Bag');

        $this->withSession([
            CarrinhoSessao::CHAVE_SESSAO => [[
                'id' => 'line-b',
                'product_id' => $second->id,
                'color' => 'Blue',
                'quantity' => 2,
            ]],
        ])->get(route('carrinho'))
            ->assertSee('Session Two Bag')
            ->assertDontSee('Session One Bag');
    }

    public function test_adicionar_nao_altera_estoque_do_produto(): void
    {
        $produto = $this->product(['qty' => 15]);

        $this->addItem($produto, ['quantity' => 3]);

        $this->assertSame(15, $produto->fresh()->qty);
    }

    public function test_get_nao_adiciona_itens(): void
    {
        $produto = $this->product();

        $this->get('/carrinho/itens?product_id='.$produto->id.'&color=Red&quantity=1')
            ->assertMethodNotAllowed();

        $this->assertSame([], session(CarrinhoSessao::CHAVE_SESSAO, []));
    }

    public function test_usuario_autenticado_usa_o_mesmo_carrinho_da_sessao(): void
    {
        $produto = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->addItem($produto, ['quantity' => 1])
            ->assertRedirect(route('carrinho'));

        $this->actingAs($user)
            ->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Cart Bag');
    }

    public function test_mudancas_no_catalogo_apos_adicionar_sao_sinalizadas(): void
    {
        $produto = $this->product(['qty' => 10, 'price' => '19.90', 'colors' => ['Red', 'Yellow']]);

        $this->addItem($produto, ['color' => 'Red', 'quantity' => 4]);
        $this->addItem($produto, ['color' => 'Yellow', 'quantity' => 2]);

        $produto->update([
            'qty' => 3,
            'colors' => ['Yellow'],
            'price' => '29.90',
        ]);

        $response = $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Cart Bag')
            ->assertSee('R$ 29,90')
            ->assertDontSee('R$ 19,90')
            ->assertSee('Este item precisa de ajuste')
            ->assertSee('value="4"', false)
            ->assertSee('value="2"', false);

        $this->assertCount(2, session(CarrinhoSessao::CHAVE_SESSAO));
        $this->assertSame(4, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantity']);
        $this->assertSame(2, session(CarrinhoSessao::CHAVE_SESSAO)[1]['quantity']);
        $this->assertTrue($response->baseResponse->isOk());

        $produto->update(['qty' => 0]);

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
        $produto = $this->product(['name' => 'Vitrine Link Bag']);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee(route('loja.produtos.exibir', $produto), false)
            ->assertDontSee(route('loja.carrinho.itens.adicionar'), false);
    }

    public function test_detalhes_habilitam_adicionar_ao_carrinho_quando_disponivel(): void
    {
        $produto = $this->product();

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertOk()
            ->assertSee(route('loja.carrinho.itens.adicionar'), false)
            ->assertSee('name="quantity"', false)
            ->assertSee('name="color"', false)
            ->assertSee('Selecione uma cor');
    }
}
