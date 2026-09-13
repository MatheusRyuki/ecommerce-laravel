<?php

namespace Tests\Feature;

use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Models\Usuario;
use App\Services\CriadorProduto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminProdutoSalvarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, UploadedFile>|null  $images
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(?array $images = null, array $overrides = []): array
    {
        return array_merge([
            'imagens' => $images ?? [
                UploadedFile::fake()->image('cover.png', 20, 20),
                UploadedFile::fake()->image('side.jpg', 20, 20),
            ],
            'nome' => 'Bolsa de lona demo',
            'preco' => '19.90',
            'cores' => ['Vermelho', 'Azul'],
            'descricao_curta' => 'Bolsa pequena para demonstração.',
            'quantidade' => 7,
            'sku' => 'tote-001',
            'descricao' => '<p>Bolsa de <strong>lona</strong> macia.</p>',
        ], $overrides);
    }

    public function test_administrador_pode_cadastrar_produto_com_duas_imagens(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.produtos.salvar'), $this->validPayload());

        $response->assertRedirect(route('admin.produtos.listar'));
        $response->assertSessionHas('status', 'produto-criado');

        $produto = Produto::query()->where('sku', 'TOTE-001')->first();
        $this->assertNotNull($produto);
        $this->assertSame('Bolsa de lona demo', $produto->nome);
        $this->assertSame('19.90', $produto->preco);
        $this->assertSame(['Vermelho', 'Azul'], $produto->cores);
        $this->assertSame(7, $produto->quantidade);

        $this->assertCount(2, $produto->imagens);
        $this->assertSame(0, $produto->imagens[0]->posicao);
        $this->assertSame(1, $produto->imagens[1]->posicao);
        Storage::disk('public')->assertExists($produto->imagens[0]->caminho);
        Storage::disk('public')->assertExists($produto->imagens[1]->caminho);
        $this->assertSame($produto->imagens[0]->caminho, $produto->imagemCapa()?->caminho);
    }

    public function test_sku_mantem_zeros_a_esquerda_como_texto(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
            'sku' => '007abc',
        ]))->assertRedirect(route('admin.produtos.listar'));

        $this->assertDatabaseHas('produtos', ['sku' => '007ABC']);
        $this->assertSame('007ABC', Produto::query()->value('sku'));
    }

    public function test_sku_duplicado_e_rejeitado_apos_normalizacao(): void
    {
        Storage::fake('public');
        Produto::factory()->create(['sku' => 'ABC-1']);
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'sku' => ' abc-1 ',
            ]))
            ->assertRedirect(route('admin.produtos.criar'))
            ->assertSessionHasErrors('sku');

        $this->assertSame(1, Produto::query()->count());
        $this->assertSame(0, ImagemProduto::query()->count());
    }

    public function test_campos_obrigatorios_limites_e_cores_invalidas_sao_rejeitados(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), [])
            ->assertRedirect(route('admin.produtos.criar'))
            ->assertSessionHasErrors(['imagens', 'nome', 'preco', 'cores', 'descricao_curta', 'quantidade', 'sku', 'descricao']);

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'preco' => '100.999',
                'quantidade' => -1,
                'cores' => ['Roxo'],
            ]))
            ->assertSessionHasErrors(['preco', 'quantidade', 'cores.0']);

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'preco' => '100000000',
            ]))
            ->assertSessionHasErrors('preco');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'descricao' => '<p><br></p>',
            ]))
            ->assertSessionHasErrors('descricao');

        $this->assertSame(0, Produto::query()->count());
    }

    public function test_imagens_invalidas_sao_rejeitadas_sem_registros_parciais(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([]))
            ->assertSessionHasErrors('imagens');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
                UploadedFile::fake()->image('e.jpg'),
                UploadedFile::fake()->image('f.jpg'),
            ]))
            ->assertSessionHasErrors('imagens');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([
                UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('imagens.0');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([
                UploadedFile::fake()->image('huge.jpg')->size(2049),
            ]))
            ->assertSessionHasErrors('imagens.0');

        $this->assertSame(0, Produto::query()->count());
        $this->assertSame(0, ImagemProduto::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_descricao_e_sanitizada_e_marcacao_permitida_e_mantida(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
            'sku' => 'SAFE-1',
            'descricao' => '<p>Safe <strong>bold</strong> <em>and</em> <a href="https://example.test">link</a><script>alert(1)</script></p><a href="javascript:alert(1)">bad</a>',
        ]))->assertRedirect(route('admin.produtos.listar'));

        $description = Produto::query()->where('sku', 'SAFE-1')->value('descricao');
        $this->assertStringContainsString('<strong>bold</strong>', $description);
        $this->assertStringContainsString('<em>and</em>', $description);
        $this->assertStringContainsString('https://example.test', $description);
        $this->assertStringNotContainsString('<script', $description);
        $this->assertStringNotContainsString('javascript:', $description);
    }

    public function test_falha_de_armazenamento_desfaz_registros_e_apaga_arquivos_novos(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('products/kept-should-be-deleted.jpg');
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with('products/kept-should-be-deleted.jpg')->andReturn(true);

        $this->app->instance(CriadorProduto::class, new CriadorProduto($disk));

        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), $this->validPayload())
            ->assertRedirect(route('admin.produtos.criar'))
            ->assertSessionHasErrors('imagens');

        $this->assertSame(0, Produto::query()->count());
        $this->assertSame(0, ImagemProduto::query()->count());
    }

    public function test_visitante_nao_pode_cadastrar_produto(): void
    {
        $this->post(route('admin.produtos.salvar'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertSame(0, Produto::query()->count());
    }

    public function test_usuario_comum_nao_pode_cadastrar_produto(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)
            ->post(route('admin.produtos.salvar'), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, Produto::query()->count());
    }
}
