<?php

namespace Tests\Feature;

use App\Models\Produto;
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

    public function test_usuario_comum_nao_ve_links_administrativos(): void
    {
        $user = Usuario::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Administração'))
            ->assertDontSee(__('Products'))
            ->assertDontSee(route('admin.produtos.listar'), false)
            ->assertDontSee(route('admin.painel'), false);
    }

    public function test_administrador_ve_produtos_e_nao_administracao_no_menu(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Administração'))
            ->assertSee('Produtos')
            ->assertSee(route('admin.produtos.listar'), false);
    }

    public function test_link_conta_do_menu_aponta_para_o_perfil(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $html = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="nav-conta"', $html);
        $this->assertStringContainsString('href="'.route('perfil.editar').'"', $this->atributosDoLink($html, 'nav-conta'));
        $this->assertStringContainsString('href="'.route('perfil.editar').'"', $this->atributosDoLink($html, 'nav-conta-movel'));
    }

    public function test_produtos_fica_ativo_na_listagem_e_conta_nao(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $html = $this->actingAs($admin)
            ->get(route('admin.produtos.listar'))
            ->assertOk()
            ->getContent();

        $this->assertLinkAtivo($html, 'nav-produtos');
        $this->assertLinkAtivo($html, 'nav-produtos-movel');
        $this->assertLinkInativo($html, 'nav-conta');
        $this->assertLinkInativo($html, 'nav-conta-movel');
        $this->assertSame(1, preg_match_all('/id="titulo-produtos"/', $html));
        $this->assertDoesNotMatchRegularExpression('/<h3[^>]*>\s*Produtos\s*<\/h3>/', $html);
    }

    public function test_conta_fica_ativa_no_perfil_e_produtos_nao(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $html = $this->actingAs($admin)
            ->get(route('perfil.editar'))
            ->assertOk()
            ->getContent();

        $this->assertLinkAtivo($html, 'nav-conta');
        $this->assertLinkAtivo($html, 'nav-conta-movel');
        $this->assertLinkInativo($html, 'nav-produtos');
        $this->assertLinkInativo($html, 'nav-produtos-movel');
    }

    public function test_produtos_fica_ativo_no_cadastro_e_na_edicao(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $produto = Produto::factory()->create();

        $cadastro = $this->actingAs($admin)
            ->get(route('admin.produtos.criar'))
            ->assertOk()
            ->getContent();

        $this->assertLinkAtivo($cadastro, 'nav-produtos');
        $this->assertLinkInativo($cadastro, 'nav-conta');

        $edicao = $this->actingAs($admin)
            ->get(route('admin.produtos.editar', $produto))
            ->assertOk()
            ->getContent();

        $this->assertLinkAtivo($edicao, 'nav-produtos');
        $this->assertLinkInativo($edicao, 'nav-conta');
    }

    public function test_usuario_comum_nao_ve_produtos_no_perfil(): void
    {
        $user = Usuario::factory()->create(['administrador' => false]);

        $this->actingAs($user)
            ->get(route('perfil.editar'))
            ->assertOk()
            ->assertDontSee(route('admin.produtos.listar'), false)
            ->assertDontSee('id="nav-produtos"', false)
            ->assertDontSee('id="nav-produtos-movel"', false);
    }

    private function atributosDoLink(string $html, string $id): string
    {
        $this->assertSame(1, preg_match('/<a\b[^>]*\bid="'.preg_quote($id, '/').'"[^>]*>/', $html, $matches));

        return $matches[0];
    }

    private function assertLinkAtivo(string $html, string $id): void
    {
        $atributos = $this->atributosDoLink($html, $id);
        $this->assertStringContainsString('border-indigo-400', $atributos);
        $this->assertStringContainsString('aria-current="page"', $atributos);
    }

    private function assertLinkInativo(string $html, string $id): void
    {
        $atributos = $this->atributosDoLink($html, $id);
        $this->assertStringNotContainsString('border-indigo-400', $atributos);
        $this->assertStringNotContainsString('aria-current="page"', $atributos);
    }
}
