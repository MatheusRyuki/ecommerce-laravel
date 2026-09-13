<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProdutoCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get(route('admin.produtos.criar'))
            ->assertRedirect(route('login'));
    }

    public function test_usuario_comum_e_proibido(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)
            ->get(route('admin.produtos.criar'))
            ->assertForbidden();
    }

    public function test_administrador_pode_ver_formulario_de_cadastro(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.produtos.criar'));

        $response->assertOk();
        $response->assertSee('name="imagens[]"', false);
        $response->assertSee('name="nome"', false);
        $response->assertSee('name="preco"', false);
        $response->assertSee('name="cores[]"', false);
        $response->assertSee('name="descricao_curta"', false);
        $response->assertSee('name="quantidade"', false);
        $response->assertSee('name="sku"', false);
        $response->assertSee('name="descricao"', false);
        $response->assertSee('Descrição curta');
        $response->assertSee('Voltar');
        $response->assertSee(route('admin.produtos.listar'), false);
        $response->assertSee(route('admin.produtos.salvar'), false);
        $response->assertSee('Preço (BRL)');
        $response->assertDontSee('disabled', false);
    }

    public function test_administrador_ve_link_de_cadastro_no_painel(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.painel'))
            ->assertOk()
            ->assertSee(route('admin.produtos.criar'), false)
            ->assertSee(route('admin.produtos.listar'), false)
            ->assertSee('Produtos')
            ->assertSee('Cadastrar produto');
    }
}
