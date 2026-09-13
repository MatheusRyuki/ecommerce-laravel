<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_perfil_e_exibida(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/perfil');

        $response->assertOk();
    }

    public function test_dados_do_perfil_podem_ser_atualizados(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/perfil', [
                'name' => 'Usuario de teste',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/perfil');

        $user->refresh();

        $this->assertSame('Usuario de teste', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_status_de_verificacao_nao_muda_se_email_permanece(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/perfil', [
                'name' => 'Usuario de teste',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/perfil');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_usuario_pode_excluir_a_conta(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/perfil', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_senha_correta_e_obrigatoria_para_excluir_conta(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/perfil')
            ->delete('/perfil', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('exclusao_usuario', 'password')
            ->assertRedirect('/perfil');

        $this->assertNotNull($user->fresh());
    }
}
