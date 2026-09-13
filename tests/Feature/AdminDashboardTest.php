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

    public function test_administrador_pode_acessar_o_painel(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.painel'));

        $response->assertOk();
        $response->assertSee($admin->name);
        $response->assertSee($admin->email);
        $response->assertSee('Painel administrativo');
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
