<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_cadastro_pode_ser_exibida(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_novos_usuarios_podem_se_cadastrar(): void
    {
        $response = $this->post('/register', [
            'name' => 'Usuario de teste',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }
}
