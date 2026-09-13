<?php

namespace Tests\Feature;

use App\Models\ImagemProduto;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProdutoIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get(route('admin.produtos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_usuario_comum_e_proibido(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)
            ->get(route('admin.produtos.index'))
            ->assertForbidden();
    }

    public function test_administrador_ve_estado_vazio(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee('Nenhum produto cadastrado ainda.')
            ->assertSee(route('admin.produtos.criar'), false)
            ->assertSee('Cadastrar produto');
    }

    public function test_administrador_ve_dados_preco_sku_e_capa(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        $coverPath = 'products/cover-demo.png';
        Storage::disk('public')->put($coverPath, UploadedFile::fake()->image('cover-demo.png')->get());

        $produto = Produto::factory()->create([
            'nome' => 'Bolsa Demo Curso',
            'sku' => 'DEMO-0001',
            'preco' => '49.90',
            'quantidade' => 12,
            'cores' => ['Vermelho', 'Amarelo'],
        ]);

        ImagemProduto::factory()->create([
            'produto_id' => $produto->id,
            'path' => $coverPath,
            'posicao' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.produtos.index'));

        $response->assertOk();
        $response->assertSee('Bolsa Demo Curso');
        $response->assertSee('DEMO-0001');
        $response->assertSee('R$ 49,90');
        $response->assertSee('12');
        $response->assertSee('Vermelho');
        $response->assertSee('Amarelo');
        $response->assertSee(Storage::disk('public')->url($coverPath), false);
        $response->assertDontSee($produto->descricao, false);
    }

    public function test_produtos_sao_ordenados_e_paginados_em_dezesseis(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $now = now();

        foreach (range(1, 16) as $index) {
            Produto::factory()->create([
                'nome' => 'Produto listado '.$index,
                'sku' => sprintf('PAGE-%02d', $index),
                'created_at' => $now->copy()->subMinutes(16 - $index),
                'updated_at' => $now->copy()->subMinutes(16 - $index),
            ]);
        }

        $firstPage = $this->actingAs($admin)->get(route('admin.produtos.index'));
        $firstPage->assertOk();
        $firstPage->assertSee('PAGE-16');
        $firstPage->assertSee('PAGE-02');
        $firstPage->assertDontSee('PAGE-01');
        $firstPage->assertSee('page=2', false);

        $secondPage = $this->actingAs($admin)->get(route('admin.produtos.index', ['page' => 2]));
        $secondPage->assertOk();
        $secondPage->assertSee('Produto listado 1');
        $secondPage->assertSee('PAGE-01');
        $secondPage->assertDontSee('PAGE-16');
    }

    public function test_cadastro_redireciona_a_listagem_com_status_e_produto_novo_primeiro(): void
    {
        Storage::fake('public');
        $admin = Usuario::factory()->admin()->create();

        Produto::factory()->create([
            'nome' => 'Produto antigo',
            'sku' => 'OLD-1',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.produtos.salvar'), [
                'imagens' => [UploadedFile::fake()->image('cover.png', 20, 20)],
                'nome' => 'Produto novo',
                'preco' => '10.00',
                'cores' => ['Verde'],
                'descricao_curta' => 'Newest item',
                'quantidade' => 3,
                'sku' => 'fresh-1',
                'descricao' => '<p>Newest item</p>',
            ])
            ->assertRedirect(route('admin.produtos.index'))
            ->assertSessionHas('status', 'produto-criado');

        $html = $this->actingAs($admin)
            ->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee('Produto cadastrado.')
            ->assertSee('Produto novo')
            ->assertSee('Produto antigo')
            ->getContent();

        $this->assertTrue(
            strpos($html, 'Produto novo') < strpos($html, 'Produto antigo'),
            'The newly created product should appear before older products.',
        );
    }

    public function test_administrador_ve_link_de_produtos_na_navegacao(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Produtos')
            ->assertSee(route('admin.produtos.index'), false);
    }

    public function test_usuario_comum_nao_ve_link_de_produtos_na_navegacao(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.produtos.index'), false);
    }

    public function test_capa_ausente_usa_reserva(): void
    {
        $admin = Usuario::factory()->admin()->create();
        Produto::factory()->create(['nome' => 'Produto sem imagem']);

        $this->actingAs($admin)
            ->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee('Sem capa')
            ->assertSee('Produto sem imagem');
    }
}
