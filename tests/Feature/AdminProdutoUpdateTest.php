<?php

namespace Tests\Feature;

use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Models\Usuario;
use App\Services\AtualizadorProduto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminProdutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $paths
     */
    private function produtoComImagens(array $overrides = [], array $paths = ['products/cover.png']): Produto
    {
        $produto = Produto::factory()->create($overrides);

        foreach (array_values($paths) as $position => $path) {
            Storage::disk('public')->put($path, 'image-'.$position);
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
     * @return array<string, mixed>
     */
    private function payload(Produto $produto, array $overrides = []): array
    {
        return array_merge([
            'nome' => $produto->nome,
            'preco' => $produto->preco,
            'cores' => $produto->cores,
            'descricao_curta' => $produto->descricao_curta,
            'quantidade' => $produto->quantidade,
            'sku' => $produto->sku,
            'descricao' => $produto->descricao,
        ], $overrides);
    }

    public function test_visitante_e_redirecionado_de_editar_e_atualizar(): void
    {
        $produto = Produto::factory()->create();

        $this->get(route('admin.produtos.editar', $produto))
            ->assertRedirect(route('login'));

        $this->patch(route('admin.produtos.atualizar', $produto), [])
            ->assertRedirect(route('login'));
    }

    public function test_usuario_comum_nao_pode_editar_nem_atualizar(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);
        $produto = Produto::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.produtos.editar', $produto))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('admin.produtos.atualizar', $produto), [])
            ->assertForbidden();
    }

    public function test_produto_ausente_retorna_nao_encontrado_para_administrador(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/produtos/99999/editar')
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch('/admin/produtos/99999', [])
            ->assertNotFound();
    }

    public function test_formulario_de_edicao_vem_preenchido_e_sku_pode_permanecer(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens([
            'nome' => 'Bolsa Demo Curso',
            'sku' => 'DEMO-0001',
            'preco' => '49.90',
            'quantidade' => 12,
            'cores' => ['Vermelho', 'Amarelo'],
            'descricao_curta' => 'Produto ficticio',
            'descricao' => '<p>Bolsa de <strong>estudo</strong>.</p>',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.produtos.editar', $produto))
            ->assertOk()
            ->assertSee('Bolsa Demo Curso')
            ->assertSee('DEMO-0001')
            ->assertSee('value="49.90"', false)
            ->assertSee('Produto ficticio')
            ->assertSee('estudo')
            ->assertSee('Capa')
            ->assertSee('Salvar alterações')
            ->assertSee('name="_method"', false)
            ->assertSee('value="PATCH"', false);

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $produto))
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'sku' => ' demo-0001 ',
                'quantidade' => 20,
            ]))
            ->assertRedirect(route('admin.produtos.listar'))
            ->assertSessionHas('status', 'produto-atualizado');

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'sku' => 'DEMO-0001',
            'quantidade' => 20,
        ]);
        $this->assertSame(1, $produto->imagens()->count());
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_sku_duplicado_de_outro_produto_e_rejeitado(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        Produto::factory()->create(['sku' => 'TAKEN-1']);
        $produto = $this->produtoComImagens(['sku' => 'KEEP-1']);

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $produto))
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'sku' => ' taken-1 ',
            ]))
            ->assertRedirect(route('admin.produtos.editar', $produto))
            ->assertSessionHasErrors('sku');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'sku' => 'KEEP-1']);
    }

    public function test_atualizacao_invalida_preserva_dados_e_arquivos(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens([
            'nome' => 'Original Name',
            'sku' => 'ORIG-1',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $produto))
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'nome' => '',
                'preco' => '12.999',
            ]))
            ->assertRedirect(route('admin.produtos.editar', $produto))
            ->assertSessionHasErrors(['nome', 'preco'])
            ->assertSessionHasInput('nome', '');

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Original Name',
            'sku' => 'ORIG-1',
        ]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_atualizacao_sem_arquivos_novos_mantem_imagens(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens([], ['products/a.png', 'products/b.png']);

        $this->actingAs($admin)
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'nome' => 'Renamed',
            ]))
            ->assertRedirect(route('admin.produtos.listar'));

        $produto->refresh()->load('imagens');
        $this->assertSame('Renamed', $produto->nome);
        $this->assertSame(['products/a.png', 'products/b.png'], $produto->imagens->pluck('caminho')->all());
        $this->assertSame([0, 1], $produto->imagens->pluck('posicao')->all());
    }

    public function test_imagens_podem_ser_adicionadas_removidas_e_capa_muda(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens([], [
            'products/cover-old.png',
            'products/side.png',
        ]);
        $coverId = $produto->imagens[0]->id;

        $this->actingAs($admin)
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'ids_imagens_remover' => [$coverId],
                'imagens' => [UploadedFile::fake()->image('extra.jpg', 20, 20)],
            ]))
            ->assertRedirect(route('admin.produtos.listar'));

        $produto->refresh()->load('imagens');
        $this->assertCount(2, $produto->imagens);
        $this->assertSame('products/side.png', $produto->imagens[0]->caminho);
        $this->assertSame(0, $produto->imagens[0]->posicao);
        $this->assertSame(1, $produto->imagens[1]->posicao);
        $this->assertStringStartsWith('products/', $produto->imagens[1]->caminho);
        Storage::disk('public')->assertMissing('products/cover-old.png');
        Storage::disk('public')->assertExists('products/side.png');
        Storage::disk('public')->assertExists($produto->imagens[1]->caminho);
    }

    public function test_quantidade_final_de_imagens_nao_pode_ser_zero_nem_acima_de_cinco(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $single = $this->produtoComImagens(['sku' => 'ONE-1'], ['products/only.png']);

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $single))
            ->patch(route('admin.produtos.atualizar', $single), $this->payload($single, [
                'ids_imagens_remover' => [$single->imagens[0]->id],
            ]))
            ->assertSessionHasErrors('imagens');

        $this->assertSame(1, $single->imagens()->count());
        Storage::disk('public')->assertExists('products/only.png');

        $full = $this->produtoComImagens(['sku' => 'FIVE-1'], [
            'products/1.png',
            'products/2.png',
            'products/3.png',
            'products/4.png',
            'products/5.png',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $full))
            ->patch(route('admin.produtos.atualizar', $full), $this->payload($full, [
                'imagens' => [UploadedFile::fake()->image('sixth.jpg')],
            ]))
            ->assertSessionHasErrors('imagens');

        $this->assertSame(5, $full->imagens()->count());
        Storage::disk('public')->assertExists('products/1.png');
    }

    public function test_remover_imagem_de_outro_produto_e_rejeitado(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens(['sku' => 'OWN-1'], ['products/own.png']);
        $other = $this->produtoComImagens(['sku' => 'OTH-1'], ['products/other.png']);

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $produto))
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'ids_imagens_remover' => [$other->imagens[0]->id],
            ]))
            ->assertRedirect(route('admin.produtos.editar', $produto))
            ->assertSessionHasErrors('ids_imagens_remover.0');

        $this->assertDatabaseHas('imagens_produto', [
            'id' => $produto->imagens[0]->id,
            'caminho' => 'products/own.png',
        ]);
        $this->assertDatabaseHas('imagens_produto', [
            'id' => $other->imagens[0]->id,
            'caminho' => 'products/other.png',
        ]);
    }

    public function test_falha_de_armazenamento_mantem_arquivos_antigos_e_remove_novos(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens(['nome' => 'Untouched'], ['products/keep.png']);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('products/tmp-new.png');
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with('products/tmp-new.png')->andReturn(true);

        $this->app->instance(AtualizadorProduto::class, new AtualizadorProduto($disk));

        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.produtos.editar', $produto))
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'nome' => 'Should Not Save',
                'imagens' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.jpg'),
                ],
            ]))
            ->assertRedirect(route('admin.produtos.editar', $produto))
            ->assertSessionHasErrors('imagens');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'nome' => 'Untouched']);
        $this->assertDatabaseHas('imagens_produto', ['produto_id' => $produto->id, 'caminho' => 'products/keep.png']);
        Storage::disk('public')->assertExists('products/keep.png');
    }

    public function test_falha_fisica_apos_commit_nao_desfaz_atualizacao(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens(['nome' => 'Before'], ['products/remove-me.png']);
        $removeId = $produto->imagens[0]->id;

        Log::spy();
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('products/brand-new.png');
        $disk->shouldReceive('delete')->once()->with('products/remove-me.png')->andReturn(false);

        $this->app->instance(AtualizadorProduto::class, new AtualizadorProduto($disk));

        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.produtos.atualizar', $produto), $this->payload($produto, [
                'nome' => 'After Commit',
                'ids_imagens_remover' => [$removeId],
                'imagens' => [UploadedFile::fake()->image('brand-new.png')],
            ]))
            ->assertRedirect(route('admin.produtos.listar'))
            ->assertSessionHas('status', 'produto-atualizado');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'nome' => 'After Commit']);
        $this->assertDatabaseHas('imagens_produto', ['produto_id' => $produto->id, 'caminho' => 'products/brand-new.png']);
        $this->assertDatabaseMissing('imagens_produto', ['id' => $removeId]);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_listagem_inclui_link_de_edicao(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $produto = Produto::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.produtos.listar'))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee(route('admin.produtos.editar', $produto), false);
    }
}
