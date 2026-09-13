<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $response = $this->get(route('admin.painel'));

        $response->assertRedirect(route('login'));
    }

    public function test_usuario_comum_e_proibido(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $response = $this->actingAs($user)->get(route('admin.painel'));

        $response->assertForbidden();
    }

    public function test_administrador_e_encaminhado_do_painel_a_listagem(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.painel'))
            ->assertRedirect(route('admin.produtos.listar'));
    }

    public function test_usuario_comum_ve_atalhos_da_conta(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Olá, '.$user->name)
            ->assertSee('Loja')
            ->assertSee('Carrinho')
            ->assertSee('Perfil')
            ->assertDontSee('Você entrou.');
    }

    public function test_cadastro_publico_nao_atribui_papel_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'Visitante',
            'email' => 'visitante@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'administrador' => true,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = Usuario::query()->where('email', 'visitante@example.test')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->administrador);
    }

    public function test_atualizacao_de_perfil_nao_atribui_papel_admin(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)->patch('/perfil', [
            'name' => $user->name,
            'email' => $user->email,
            'administrador' => true,
        ])->assertRedirect('/perfil');

        $this->assertFalse($user->fresh()->administrador);
    }

    public function test_usuario_comum_nao_ve_link_de_administracao(): void
    {
        $user = Usuario::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Administração'))
            ->assertDontSee(__('Products'));
    }

    public function test_administrador_ve_link_de_administracao(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administração');
    }
}
