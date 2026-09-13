<?php

namespace Tests\Feature\Auth;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_senha_pode_ser_atualizada(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/perfil')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/perfil');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_senha_atual_correta_e_obrigatoria_para_atualizar(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/perfil')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('atualizacao_senha', 'current_password')
            ->assertRedirect('/perfil');
    }
}
