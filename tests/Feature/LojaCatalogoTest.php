<?php

namespace Tests\Feature;

use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LojaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $paths
     */
    private function produtoComImagens(array $overrides = [], array $paths = ['products/cover.png']): Produto
    {
        $produto = Produto::factory()->create($overrides);

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

    public function test_catalogo_e_detalhes_sao_publicos(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens(['nome' => 'Bolsa publica']);

        $this->get(route('inicio'))->assertOk()->assertSee('Bolsa publica');
        $this->get(route('loja.produtos.exibir', $produto))->assertOk()->assertSee('Bolsa publica');
    }

    public function test_catalogo_mostra_estado_vazio(): void
    {
        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Ainda não há produtos na loja.')
            ->assertDontSee('Smart Watch');
    }

    public function test_catalogo_mostra_dados_preco_capa_e_links(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens([
            'nome' => 'Bolsa Demo Editada',
            'sku' => 'DEMO-0001',
            'preco' => '49.90',
            'quantidade' => 15,
        ], ['products/demo-cover.png']);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Bolsa Demo Editada')
            ->assertSee('R$ 49,90')
            ->assertSee(Storage::disk('public')->url('products/demo-cover.png'), false)
            ->assertSee(route('loja.produtos.exibir', $produto), false)
            ->assertDontSee(route('loja.produtos.exibir', ['produto' => $produto->id + 99]), false);
    }

    public function test_catalogo_ordena_e_pagina_treze_produtos(): void
    {
        $now = now();

        foreach (range(1, 13) as $index) {
            Produto::factory()->create([
                'nome' => sprintf('Vitrine %02d', $index),
                'sku' => sprintf('CAT-%02d', $index),
                'created_at' => $now->copy()->subMinutes(13 - $index),
                'updated_at' => $now->copy()->subMinutes(13 - $index),
            ]);
        }

        $firstPage = $this->get(route('inicio'));
        $firstPage->assertOk();
        $firstPage->assertSee('Vitrine 13');
        $firstPage->assertSee('Vitrine 02');
        $firstPage->assertDontSee('Vitrine 01');
        $firstPage->assertSee('page=2', false);

        $this->get(route('inicio', ['page' => 2]))
            ->assertOk()
            ->assertSee('Vitrine 01')
            ->assertDontSee('Vitrine 13');
    }

    public function test_dois_produtos_tem_detalhes_e_imagens_isolados(): void
    {
        Storage::fake('public');
        $first = $this->produtoComImagens([
            'nome' => 'Primeiro isolado',
            'sku' => 'ISO-1',
            'descricao' => '<p>Primeiro corpo</p>',
        ], ['products/first.png']);
        $second = $this->produtoComImagens([
            'nome' => 'Segundo isolado',
            'sku' => 'ISO-2',
            'descricao' => '<p>Second body</p>',
        ], ['products/second.png']);

        $this->get(route('loja.produtos.exibir', $first))
            ->assertOk()
            ->assertSee('Primeiro isolado')
            ->assertSee('ISO-1')
            ->assertSee('Primeiro corpo')
            ->assertSee(Storage::disk('public')->url('products/first.png'), false)
            ->assertDontSee('Segundo isolado')
            ->assertDontSee('products/second.png');

        $this->get(route('loja.produtos.exibir', $second))
            ->assertOk()
            ->assertSee('Segundo isolado')
            ->assertSee('ISO-2')
            ->assertDontSee('Primeiro isolado')
            ->assertDontSee('products/first.png');
    }

    public function test_produtos_ausentes_e_excluidos_retornam_nao_encontrado(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens();
        $id = $produto->id;
        $produto->delete();

        $this->get('/products/99999')->assertNotFound();
        $this->get('/products/'.$id)->assertNotFound();
    }

    public function test_galeria_lida_com_uma_varias_e_arquivo_ausente(): void
    {
        Storage::fake('public');
        $single = $this->produtoComImagens(['sku' => 'GAL-1'], ['products/only.png']);
        $many = $this->produtoComImagens(['sku' => 'GAL-2'], [
            'products/a.png',
            'products/b.png',
        ]);
        $missing = $this->produtoComImagens(['sku' => 'GAL-3'], ['products/gone.png']);
        Storage::disk('public')->delete('products/gone.png');

        $this->get(route('loja.produtos.exibir', $single))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('products/only.png'), false)
            ->assertDontSee('slider-navFive');

        $manyResponse = $this->get(route('loja.produtos.exibir', $many));
        $manyResponse->assertOk();
        $manyResponse->assertSee(Storage::disk('public')->url('products/a.png'), false);
        $manyResponse->assertSee(Storage::disk('public')->url('products/b.png'), false);
        $manyResponse->assertSee('slider-navFive', false);

        $this->get(route('loja.produtos.exibir', $missing))
            ->assertOk()
            ->assertSee('Sem capa');
    }

    public function test_produto_sem_estoque_aparece_como_indisponivel(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens([
            'nome' => 'Bolsa sem estoque',
            'quantidade' => 0,
        ]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Bolsa sem estoque')
            ->assertSee('Indisponível');

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertOk()
            ->assertSee('Indisponível');
    }

    public function test_texto_simples_e_escapado_e_descricao_rica_e_sanitizada(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens([
            'nome' => '<script>alert(1)</script>',
            'sku' => 'XSS-1',
            'descricao_curta' => '<b>plain</b>',
            'descricao' => '<p>Safe <strong>bold</strong><script>alert(1)</script></p><a href="javascript:alert(1)">bad</a>',
        ]);

        $response = $this->get(route('loja.produtos.exibir', $produto));
        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertSee('<strong>bold</strong>', false);
        $response->assertDontSee('javascript:', false);
        $response->assertSee('&lt;b&gt;plain&lt;/b&gt;', false);
    }

    public function test_alteracoes_e_exclusoes_admin_aparecem_no_catalogo(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens(['nome' => 'Antes da loja', 'sku' => 'SYNC-1']);

        $this->get(route('inicio'))->assertSee('Antes da loja');

        $this->actingAs($admin)->patch(route('admin.produtos.atualizar', $produto), [
            'nome' => 'Depois da loja',
            'preco' => $produto->preco,
            'cores' => $produto->cores,
            'descricao_curta' => $produto->descricao_curta,
            'sku' => $produto->sku,
            'descricao' => $produto->descricao,
            'publicado' => '1',
        ])->assertRedirect(route('admin.produtos.listar'));

        $this->get(route('inicio'))
            ->assertSee('Depois da loja')
            ->assertDontSee('Antes da loja');

        $this->actingAs($admin)
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('admin.produtos.listar'));

        $this->get(route('inicio'))
            ->assertDontSee('Depois da loja')
            ->assertSee('Ainda não há produtos na loja.');
    }

    public function test_url_antiga_de_detalhes_redireciona_ao_catalogo(): void
    {
        $this->get('/product-details')->assertRedirect(route('inicio'));
    }

    public function test_pagina_inexistente_usa_404_em_portugues(): void
    {
        $this->get('/pagina-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Página não encontrada')
            ->assertSee('Voltar à loja');
    }

    public function test_controles_de_compra_ficam_desabilitados_na_vitrine(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens();

        $this->get(route('inicio'))
            ->assertSee('Ver produto')
            ->assertSee(route('loja.produtos.exibir', $produto), false)
            ->assertDontSee(route('loja.carrinho.itens.adicionar'), false);

        $this->get(route('loja.produtos.exibir', $produto))
            ->assertDontSee('Comprar agora')
            ->assertSee('Adicionar ao carrinho')
            ->assertSee(route('loja.carrinho.itens.adicionar'), false);
    }
}
