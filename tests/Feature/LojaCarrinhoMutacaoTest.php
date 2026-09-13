<?php

namespace Tests\Feature;

use App\Cart\CarrinhoSessao;
use App\Models\Produto;
use App\Models\ImagemProduto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LojaCarrinhoMutacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $colors
     */
    private function product(array $overrides = [], array $colors = ['Red', 'Yellow']): Produto
    {
        Storage::fake('public');

        $produto = Produto::factory()->create(array_merge([
            'name' => 'Cart Bag',
            'sku' => 'CART-0001',
            'price' => '49.90',
            'qty' => 15,
            'colors' => $colors,
        ], $overrides));

        $path = 'products/cart-cover.png';
        Storage::disk('public')->put($path, 'img');
        ImagemProduto::factory()->create([
            'product_id' => $produto->id,
            'path' => $path,
            'position' => 0,
        ]);

        return $produto->refresh()->load('imagens');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function add(Produto $produto, array $overrides = []): void
    {
        $this->post(route('loja.carrinho.itens.adicionar'), array_merge([
            'product_id' => $produto->id,
            'color' => 'Red',
            'quantity' => 1,
        ], $overrides))->assertRedirect(route('carrinho'));
    }

    private function itemId(int $index = 0): string
    {
        return session(CarrinhoSessao::CHAVE_SESSAO)[$index]['id'];
    }

    public function test_atualizar_e_remover_funcionam_na_sessao_atual(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 2]);
        $id = $this->itemId();

        $this->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 4])
            ->assertRedirect(route('carrinho'))
            ->assertSessionHas('status');

        $this->assertSame(4, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantity']);

        $this->from(route('carrinho'))
            ->delete(route('loja.carrinho.itens.remover', $id))
            ->assertRedirect(route('carrinho'));

        $this->assertSame([], session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_item_desconhecido_e_outra_sessao_retornam_404(): void
    {
        $produto = $this->product();
        $this->add($produto);
        $foreignId = $this->itemId();

        $this->patch(route('loja.carrinho.itens.atualizar', (string) Str::uuid()), ['quantity' => 2])
            ->assertNotFound();
        $this->delete(route('loja.carrinho.itens.remover', (string) Str::uuid()))
            ->assertNotFound();

        $this->flushSession();
        $other = $this->product(['sku' => 'CART-0002', 'name' => 'Other Bag'], ['Blue']);
        $this->withSession([
            CarrinhoSessao::CHAVE_SESSAO => [[
                'id' => (string) Str::uuid(),
                'product_id' => $other->id,
                'color' => 'Blue',
                'quantity' => 1,
            ]],
        ])->patch(route('loja.carrinho.itens.atualizar', $foreignId), ['quantity' => 3])
            ->assertNotFound();
    }

    public function test_remover_uma_cor_mantem_a_outra_linha(): void
    {
        $produto = $this->product();
        $this->add($produto, ['color' => 'Red', 'quantity' => 2]);
        $this->add($produto, ['color' => 'Yellow', 'quantity' => 1]);
        $redId = $this->itemId(0);

        $this->delete(route('loja.carrinho.itens.remover', $redId))->assertRedirect(route('carrinho'));

        $items = session(CarrinhoSessao::CHAVE_SESSAO);
        $this->assertCount(1, $items);
        $this->assertSame('Yellow', $items[0]['color']);
        $this->assertSame(1, $items[0]['quantity']);
    }

    public function test_produtos_indisponiveis_e_excluidos_podem_ser_removidos(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 2]);
        $id = $this->itemId();
        $produto->delete();

        $this->get(route('carrinho'))->assertSee('Item indisponível');
        $this->delete(route('loja.carrinho.itens.remover', $id))->assertRedirect(route('carrinho'));
        $this->get(route('carrinho'))
            ->assertSee('Seu carrinho está vazio.')
            ->assertSee('R$ 0,00')
            ->assertSee('>0</b>', false);
    }

    public function test_patch_substitui_quantidade_em_vez_de_somar(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 2]);
        $this->patch(route('loja.carrinho.itens.atualizar', $this->itemId()), ['quantity' => 5])
            ->assertRedirect(route('carrinho'));

        $this->assertSame(5, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantity']);
        $this->assertCount(1, session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_estoque_entre_cores_rejeita_aumento_e_preserva_carrinho(): void
    {
        $produto = $this->product(['qty' => 5]);
        $this->add($produto, ['color' => 'Red', 'quantity' => 3]);
        $this->add($produto, ['color' => 'Yellow', 'quantity' => 1]);
        $previous = session(CarrinhoSessao::CHAVE_SESSAO);
        $redId = $previous[0]['id'];

        $this->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $redId), ['quantity' => 5])
            ->assertSessionHasErrors('cart_items.'.$redId);

        $this->assertSame($previous, session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_quantidade_pode_diminuir_depois_que_estoque_cai(): void
    {
        $produto = $this->product(['qty' => 10]);
        $this->add($produto, ['quantity' => 8]);
        $id = $this->itemId();
        $produto->update(['qty' => 5]);

        $this->get(route('carrinho'))
            ->assertSee('Este item precisa de ajuste')
            ->assertSee('O total dos produtos só aparece quando todos os itens estão disponíveis.')
            ->assertDontSee('Total dos produtos');

        $this->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 7])
            ->assertRedirect(route('carrinho'));
        $this->assertSame(7, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantity']);
        $this->get(route('carrinho'))->assertSee('Este item precisa de ajuste');

        $this->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 5])
            ->assertRedirect(route('carrinho'));
        $this->get(route('carrinho'))
            ->assertDontSee(__('This item needs adjustment'))
            ->assertSee('Total dos produtos')
            ->assertSee('R$ 249,50');

        $this->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 6])
            ->assertSessionHasErrors('cart_items.'.$id);
        $this->assertSame(5, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantity']);
    }

    public function test_quantidades_zero_negativas_e_fracionarias_sao_rejeitadas(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 2]);
        $id = $this->itemId();
        $previous = session(CarrinhoSessao::CHAVE_SESSAO);

        $this->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 0])->assertSessionHasErrors('cart_items.'.$id);
        $this->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => -1])->assertSessionHasErrors('cart_items.'.$id);
        $this->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 1.5])->assertSessionHasErrors('cart_items.'.$id);
        $this->assertSame($previous, session(CarrinhoSessao::CHAVE_SESSAO));
    }

    public function test_subtotais_e_total_usam_decimais_exatos_e_limites_de_preco(): void
    {
        $produto = $this->product(['price' => '49.90', 'qty' => 15]);
        $this->add($produto, ['color' => 'Red', 'quantity' => 3]);
        $this->add($produto, ['color' => 'Yellow', 'quantity' => 1]);

        $this->get(route('carrinho'))
            ->assertSee('R$ 149,70')
            ->assertSee('R$ 49,90')
            ->assertSee('R$ 199,60')
            ->assertSee('Total dos produtos');

        $expensive = $this->product([
            'name' => 'Limit Bag',
            'sku' => 'LIM-1',
            'price' => '99999999.99',
            'qty' => 2,
            'colors' => ['Blue'],
        ], ['Blue']);
        $this->flushSession();
        $this->add($expensive, ['color' => 'Blue', 'quantity' => 2]);
        $this->get(route('carrinho'))->assertSee('R$ 199.999.999,98');
    }

    public function test_preco_atualizado_no_banco_entra_nos_totais(): void
    {
        $produto = $this->product(['price' => '19.90']);
        $this->add($produto, ['quantity' => 2]);
        $produto->update(['price' => '29.90']);

        $this->get(route('carrinho'))
            ->assertSee('R$ 29,90')
            ->assertSee('R$ 59,80')
            ->assertDontSee('R$ 19,90')
            ->assertDontSee('R$ 39,80');
    }

    public function test_totais_enviados_pelo_cliente_sao_ignorados(): void
    {
        $produto = $this->product(['price' => '49.90']);
        $this->add($produto, ['quantity' => 2]);
        $id = $this->itemId();

        $this->patch(route('loja.carrinho.itens.atualizar', $id), [
            'quantity' => 2,
            'price' => '0.01',
            'total' => '0.02',
        ])->assertRedirect(route('carrinho'));

        $this->get(route('carrinho'))
            ->assertSee('R$ 99,80')
            ->assertDontSee('R$ 0,02');
    }

    public function test_remover_ultimo_item_mostra_carrinho_vazio_e_total_zero(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 3]);
        $this->delete(route('loja.carrinho.itens.remover', $this->itemId()))->assertRedirect(route('carrinho'));

        $this->get(route('carrinho'))
            ->assertSee('Seu carrinho está vazio.')
            ->assertSee('R$ 0,00')
            ->assertSee('>0</b>', false);
        $this->assertSame(15, $produto->fresh()->qty);
    }

    public function test_mutacoes_nao_alteram_estoque_do_produto(): void
    {
        $produto = $this->product(['qty' => 15]);
        $this->add($produto, ['quantity' => 2]);
        $this->patch(route('loja.carrinho.itens.atualizar', $this->itemId()), ['quantity' => 4]);
        $this->delete(route('loja.carrinho.itens.remover', $this->itemId()));

        $this->assertSame(15, $produto->fresh()->qty);
    }

    public function test_get_nao_atualiza_nem_exclui(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 2]);
        $id = $this->itemId();

        $this->get('/carrinho/itens/'.$id)->assertMethodNotAllowed();
        $this->assertSame(2, session(CarrinhoSessao::CHAVE_SESSAO)[0]['quantity']);
    }

    public function test_linha_indisponivel_nao_pode_ser_atualizada(): void
    {
        $produto = $this->product();
        $this->add($produto, ['quantity' => 2]);
        $id = $this->itemId();
        $previous = session(CarrinhoSessao::CHAVE_SESSAO);
        $produto->update(['qty' => 0]);

        $this->from(route('carrinho'))
            ->patch(route('loja.carrinho.itens.atualizar', $id), ['quantity' => 1])
            ->assertSessionHasErrors('cart_items.'.$id);
        $this->assertSame($previous, session(CarrinhoSessao::CHAVE_SESSAO));
    }
}
