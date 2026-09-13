<?php

namespace Tests\Feature\Auth;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_login_pode_ser_exibida(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_usuarios_podem_autenticar_pela_tela_de_login(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_administrador_e_redirecionado_a_listagem_de_produtos(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.produtos.listar', absolute: false));
    }

    public function test_tela_de_login_oferece_criar_conta(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Criar conta')
            ->assertSee(route('register'), false);
    }

    public function test_usuarios_nao_autenticam_com_senha_invalida(): void
    {
        $user = Usuario::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_usuarios_podem_sair(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
