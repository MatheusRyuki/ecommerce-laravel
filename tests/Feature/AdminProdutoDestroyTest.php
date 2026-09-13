<?php

namespace Tests\Feature;

use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Models\Usuario;
use App\Services\ExcluirProduto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminProdutoDestroyTest extends TestCase
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
                'path' => $path,
                'posicao' => $position,
            ]);
        }

        return $produto->refresh()->load('imagens');
    }

    public function test_visitante_nao_pode_excluir_produto(): void
    {
        Storage::fake('public');
        $produto = $this->produtoComImagens();

        $this->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('produtos', ['id' => $produto->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_usuario_comum_nao_pode_excluir_produto(): void
    {
        Storage::fake('public');
        $user = Usuario::factory()->create(['administrador' => false]);
        $produto = $this->produtoComImagens();

        $this->actingAs($user)
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertForbidden();

        $this->assertDatabaseHas('produtos', ['id' => $produto->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_administrador_pode_excluir_produto_com_varias_imagens(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens(['nome' => 'Temp Delete', 'sku' => 'TEMP-DEL-1'], [
            'products/temp-a.png',
            'products/temp-b.png',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.produtos.index'))
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('admin.produtos.index'))
            ->assertSessionHas('status', 'produto-excluido');

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
        $this->assertDatabaseMissing('imagens_produto', ['produto_id' => $produto->id]);
        Storage::disk('public')->assertMissing('products/temp-a.png');
        Storage::disk('public')->assertMissing('products/temp-b.png');
    }

    public function test_exclusao_nao_afeta_outro_produto_nem_imagens(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $keep = $this->produtoComImagens(['sku' => 'KEEP-1'], ['products/keep.png']);
        $remove = $this->produtoComImagens(['sku' => 'GONE-1'], ['products/gone.png']);

        $this->actingAs($admin)
            ->delete(route('admin.produtos.excluir', $remove))
            ->assertRedirect(route('admin.produtos.index'));

        $this->assertDatabaseHas('produtos', ['id' => $keep->id, 'sku' => 'KEEP-1']);
        $this->assertDatabaseHas('imagens_produto', ['produto_id' => $keep->id, 'path' => 'products/keep.png']);
        Storage::disk('public')->assertExists('products/keep.png');
        Storage::disk('public')->assertMissing('products/gone.png');
    }

    public function test_produto_ausente_e_exclusao_repetida_retornam_nao_encontrado(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens();
        $id = $produto->id;

        $this->actingAs($admin)
            ->delete('/admin/produtos/99999')
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('admin.produtos.index'));

        $this->actingAs($admin)
            ->delete('/admin/produtos/'.$id)
            ->assertNotFound();
    }

    public function test_get_nao_exclui_produto(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens();

        $this->actingAs($admin)
            ->get('/admin/produtos/'.$produto->id)
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('produtos', ['id' => $produto->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_exclusao_segue_se_um_arquivo_de_imagem_ja_faltava(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens(['sku' => 'MISS-1'], [
            'products/exists.png',
            'products/already-gone.png',
        ]);
        Storage::disk('public')->delete('products/already-gone.png');

        $this->actingAs($admin)
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('admin.produtos.index'))
            ->assertSessionHas('status', 'produto-excluido');

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
        Storage::disk('public')->assertMissing('products/exists.png');
    }

    public function test_falha_no_banco_preserva_registros_e_arquivos(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens(['sku' => 'FAIL-DB']);

        Produto::deleting(function (): void {
            throw new RuntimeException('Forced database failure.');
        });

        try {
            $this->actingAs($admin)
                ->delete(route('admin.produtos.excluir', $produto));
        } finally {
            Produto::flushEventListeners();
        }

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'sku' => 'FAIL-DB']);
        $this->assertDatabaseHas('imagens_produto', ['produto_id' => $produto->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_falha_fisica_apos_commit_mantem_exclusao_no_banco(): void
    {
        Storage::fake('public');
        Log::spy();
        $produto = $this->produtoComImagens(['sku' => 'PEND-1'], [
            'products/fail.png',
            'products/ok.png',
        ]);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('exists')->with('products/fail.png')->once()->andReturn(true);
        $disk->shouldReceive('delete')->with('products/fail.png')->once()->andReturn(false);
        $disk->shouldReceive('exists')->with('products/ok.png')->once()->andReturn(true);
        $disk->shouldReceive('delete')->with('products/ok.png')->once()->andReturn(true);

        $this->app->instance(ExcluirProduto::class, new ExcluirProduto($disk));

        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('admin.produtos.index'))
            ->assertSessionHas('status', 'produto-excluido-com-limpeza-pendente');

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
        $this->assertDatabaseMissing('imagens_produto', ['produto_id' => $produto->id]);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_excluir_ultimo_produto_mostra_estado_vazio(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();
        $produto = $this->produtoComImagens(['nome' => 'Only Product', 'sku' => 'ONLY-1']);

        $this->actingAs($admin)
            ->from(route('admin.produtos.index', ['page' => 2]))
            ->delete(route('admin.produtos.excluir', $produto))
            ->assertRedirect(route('admin.produtos.index'));

        $this->actingAs($admin)
            ->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee('Produto excluído.')
            ->assertSee('Nenhum produto cadastrado ainda.')
            ->assertDontSee('Only Product')
            ->assertDontSee('page=2', false);
    }

    public function test_listagem_inclui_confirmacao_de_exclusao(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $produto = Produto::factory()->create([
            'nome' => 'Listado para exclusao',
            'sku' => 'DEL-UI-1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee('Excluir')
            ->assertSee('Listado para exclusao')
            ->assertSee('DEL-UI-1')
            ->assertSee(route('admin.produtos.excluir', $produto), false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="DELETE"', false);
    }
}
