<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_nao_pode_rebaixar_a_si_mesmo(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.rebaixar', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->administrador);
    }

    public function test_promove_e_rebaixa_quando_ha_outro_administrador(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $comum = Usuario::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.promover', $comum))
            ->assertRedirect();

        $this->assertTrue($comum->fresh()->administrador);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.rebaixar', $comum))
            ->assertRedirect();

        $this->assertFalse($comum->fresh()->administrador);
    }

    public function test_busca_por_nome_ou_email(): void
    {
        $admin = Usuario::factory()->admin()->create(['name' => 'Ana Admin', 'email' => 'ana@example.test']);
        Usuario::factory()->create(['name' => 'Bruno', 'email' => 'bruno@example.test']);

        $html = $this->actingAs($admin)
            ->get(route('admin.usuarios.listar', ['q' => 'bruno@']))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, '<td>bruno@example.test</td>'));
        $this->assertSame(0, substr_count($html, '<td>ana@example.test</td>'));
    }
}
