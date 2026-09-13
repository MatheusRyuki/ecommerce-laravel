<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\ImagemProduto;
use App\Models\User;
use App\Services\CriadorProduto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminProdutoStoreTest extends TestCase
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
            'images' => $images ?? [
                UploadedFile::fake()->image('cover.png', 20, 20),
                UploadedFile::fake()->image('side.jpg', 20, 20),
            ],
            'name' => 'Demo Canvas Tote',
            'price' => '19.90',
            'colors' => ['Red', 'Blue'],
            'short_description' => 'A small tote for study demos.',
            'qty' => 7,
            'sku' => 'tote-001',
            'description' => '<p>Soft <strong>canvas</strong> tote.</p>',
        ], $overrides);
    }

    public function test_administrador_pode_cadastrar_produto_com_duas_imagens(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.produtos.salvar'), $this->validPayload());

        $response->assertRedirect(route('admin.produtos.index'));
        $response->assertSessionHas('status', 'produto-criado');

        $produto = Produto::query()->where('sku', 'TOTE-001')->first();
        $this->assertNotNull($produto);
        $this->assertSame('Demo Canvas Tote', $produto->name);
        $this->assertSame('19.90', $produto->price);
        $this->assertSame(['Red', 'Blue'], $produto->colors);
        $this->assertSame(7, $produto->qty);

        $this->assertCount(2, $produto->imagens);
        $this->assertSame(0, $produto->imagens[0]->position);
        $this->assertSame(1, $produto->imagens[1]->position);
        Storage::disk('public')->assertExists($produto->imagens[0]->path);
        Storage::disk('public')->assertExists($produto->imagens[1]->path);
        $this->assertSame($produto->imagens[0]->path, $produto->imagemCapa()?->path);
    }

    public function test_sku_mantem_zeros_a_esquerda_como_texto(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
            'sku' => '007abc',
        ]))->assertRedirect(route('admin.produtos.index'));

        $this->assertDatabaseHas('products', ['sku' => '007ABC']);
        $this->assertSame('007ABC', Produto::query()->value('sku'));
    }

    public function test_sku_duplicado_e_rejeitado_apos_normalizacao(): void
    {
        Storage::fake('public');
        Produto::factory()->create(['sku' => 'ABC-1']);
        $admin = User::factory()->admin()->create();

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
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), [])
            ->assertRedirect(route('admin.produtos.criar'))
            ->assertSessionHasErrors(['images', 'name', 'price', 'colors', 'short_description', 'qty', 'sku', 'description']);

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'price' => '100.999',
                'qty' => -1,
                'colors' => ['Purple'],
            ]))
            ->assertSessionHasErrors(['price', 'qty', 'colors.0']);

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'price' => '100000000',
            ]))
            ->assertSessionHasErrors('price');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
                'description' => '<p><br></p>',
            ]))
            ->assertSessionHasErrors('description');

        $this->assertSame(0, Produto::query()->count());
    }

    public function test_imagens_invalidas_sao_rejeitadas_sem_registros_parciais(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([]))
            ->assertSessionHasErrors('images');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
                UploadedFile::fake()->image('e.jpg'),
                UploadedFile::fake()->image('f.jpg'),
            ]))
            ->assertSessionHasErrors('images');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([
                UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('images.0');

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), $this->validPayload([
                UploadedFile::fake()->image('huge.jpg')->size(2049),
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, Produto::query()->count());
        $this->assertSame(0, ImagemProduto::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_descricao_e_sanitizada_e_marcacao_permitida_e_mantida(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.produtos.salvar'), $this->validPayload(overrides: [
            'sku' => 'SAFE-1',
            'description' => '<p>Safe <strong>bold</strong> <em>and</em> <a href="https://example.test">link</a><script>alert(1)</script></p><a href="javascript:alert(1)">bad</a>',
        ]))->assertRedirect(route('admin.produtos.index'));

        $description = Produto::query()->where('sku', 'SAFE-1')->value('description');
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

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.produtos.criar'))
            ->post(route('admin.produtos.salvar'), $this->validPayload())
            ->assertRedirect(route('admin.produtos.criar'))
            ->assertSessionHasErrors('images');

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
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->post(route('admin.produtos.salvar'), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, Produto::query()->count());
    }
}
