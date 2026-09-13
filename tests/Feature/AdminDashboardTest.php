<?php

namespace Tests\Feature;

use App\Models\User;
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
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.painel'));

        $response->assertForbidden();
    }

    public function test_administrador_pode_acessar_o_painel(): void
    {
        $admin = User::factory()->admin()->create();

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
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'visitante@example.test')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->is_admin);
    }

    public function test_atualizacao_de_perfil_nao_atribui_papel_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => true,
        ])->assertRedirect('/profile');

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_usuario_comum_nao_ve_link_de_administracao(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Administração'))
            ->assertDontSee(__('Products'));
    }

    public function test_administrador_ve_link_de_administracao(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administração');
    }
}
