<?php

namespace Tests\Feature;

use App\Cart\CarrinhoSessao;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicacaoCatalogoFavoritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_produto_oculto_nao_aparece_na_vitrine_e_detalhe_e_404(): void
    {
        $oculto = Produto::factory()->oculto()->create(['nome' => 'Segredo', 'sku' => 'HID-1']);
        $publico = Produto::factory()->create(['nome' => 'Visivel', 'sku' => 'VIS-1']);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Visivel')
            ->assertDontSee('Segredo');

        $this->get(route('loja.produtos.exibir', $oculto))->assertNotFound();
        $this->get(route('loja.produtos.exibir', $publico))->assertOk();
    }

    public function test_busca_e_filtros_da_vitrine(): void
    {
        Produto::factory()->create(['nome' => 'Bolsa Verde Linda', 'sku' => 'BVL-1', 'cores' => ['Verde'], 'quantidade' => 2]);
        Produto::factory()->create(['nome' => 'Bolsa Vermelha', 'sku' => 'BVR-1', 'cores' => ['Vermelho'], 'quantidade' => 0]);

        $this->get(route('inicio', ['q' => 'BVL-1']))
            ->assertOk()
            ->assertSee('Bolsa Verde Linda')
            ->assertDontSee('Bolsa Vermelha');

        $this->get(route('inicio', ['cor' => 'Verde']))
            ->assertSee('Bolsa Verde Linda')
            ->assertDontSee('Bolsa Vermelha');

        $this->get(route('inicio', ['disponivel' => '1']))
            ->assertSee('Bolsa Verde Linda')
            ->assertDontSee('Bolsa Vermelha');
    }

    public function test_categoria_filtra_e_slug_invalido_e_404(): void
    {
        $cat = Categoria::query()->create(['nome' => 'Bolsas', 'slug' => 'bolsas']);
        Produto::factory()->create(['nome' => 'Na categoria', 'categoria_id' => $cat->id]);
        Produto::factory()->create(['nome' => 'Fora']);

        $this->get(route('loja.categorias.exibir', $cat))
            ->assertOk()
            ->assertSee('Na categoria')
            ->assertDontSee('Fora');

        $this->get('/categorias/nao-existe')->assertNotFound();
    }

    public function test_oculto_nao_entra_no_carrinho_e_fica_indisponivel_se_ja_estava(): void
    {
        $produto = Produto::factory()->create(['quantidade' => 5, 'cores' => ['Verde']]);

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'produto_id' => $produto->id,
            'cor' => 'Verde',
            'quantidade' => 1,
        ])->assertRedirect(route('carrinho'));

        $produto->update(['publicado' => false]);

        $this->get(route('carrinho'))
            ->assertOk()
            ->assertSee('Indisponível');

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'produto_id' => $produto->id,
            'cor' => 'Verde',
            'quantidade' => 1,
        ])->assertSessionHasErrors('carrinho');
    }

    public function test_favorito_exige_login_e_lista_so_publicados(): void
    {
        $produto = Produto::factory()->create();
        $this->post(route('conta.favoritos.adicionar', $produto))->assertRedirect(route('login'));

        $user = Usuario::factory()->create();
        $this->actingAs($user)->post(route('conta.favoritos.adicionar', $produto))->assertRedirect();
        $this->actingAs($user)->get(route('conta.favoritos'))->assertOk()->assertSee($produto->nome);

        $produto->update(['publicado' => false]);
        $this->actingAs($user)->get(route('conta.favoritos'))->assertOk()->assertDontSee($produto->nome);
    }

    public function test_mescla_carrinho_no_login(): void
    {
        $produto = Produto::factory()->create(['quantidade' => 5, 'cores' => ['Verde']]);
        $user = Usuario::factory()->create();

        $this->post(route('loja.carrinho.itens.adicionar'), [
            'produto_id' => $produto->id,
            'cor' => 'Verde',
            'quantidade' => 2,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertSame([], session(CarrinhoSessao::CHAVE_SESSAO) ?? []);
        $this->assertDatabaseHas('itens_carrinho', [
            'usuario_id' => $user->id,
            'produto_id' => $produto->id,
            'cor' => 'Verde',
            'quantidade' => 2,
        ]);
    }
}
