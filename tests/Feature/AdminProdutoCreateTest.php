<?php

namespace Tests\Feature;

use App\Models\User;
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
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.produtos.criar'))
            ->assertForbidden();
    }

    public function test_administrador_pode_ver_formulario_de_cadastro(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.produtos.criar'));

        $response->assertOk();
        $response->assertSee('name="images[]"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="price"', false);
        $response->assertSee('name="colors[]"', false);
        $response->assertSee('name="short_description"', false);
        $response->assertSee('name="qty"', false);
        $response->assertSee('name="sku"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('Descrição curta');
        $response->assertSee('Voltar');
        $response->assertSee(route('admin.produtos.index'), false);
        $response->assertSee(route('admin.produtos.salvar'), false);
        $response->assertSee('Preço (BRL)');
        $response->assertDontSee('disabled', false);
    }

    public function test_administrador_ve_link_de_cadastro_no_painel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.painel'))
            ->assertOk()
            ->assertSee(route('admin.produtos.criar'), false)
            ->assertSee(route('admin.produtos.index'), false)
            ->assertSee('Produtos')
            ->assertSee('Cadastrar produto');
    }
}
