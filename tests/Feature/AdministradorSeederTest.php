<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AdministradorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdministradorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_cria_administrador_com_senha_hasheada(): void
    {
        config([
            'admin.name' => 'Administrador',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'senha-forte-de-teste-123',
        ]);

        $this->seed(AdministradorSeeder::class);

        $administrador = Usuario::query()->where('email', 'admin@example.test')->first();

        $this->assertNotNull($administrador);
        $this->assertTrue($administrador->administrador);
        $this->assertSame('Administrador', $administrador->name);
        $this->assertNotSame('senha-forte-de-teste-123', $administrador->getRawOriginal('password'));
        $this->assertTrue(Hash::check('senha-forte-de-teste-123', $administrador->password));
    }

    public function test_seeder_pode_rodar_de_novo_sem_duplicar_nem_trocar_senha(): void
    {
        config([
            'admin.name' => 'Administrador',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'senha-inicial-123456789',
        ]);

        $this->seed(AdministradorSeeder::class);

        $administrador = Usuario::query()->where('email', 'admin@example.test')->first();
        $hashOriginal = $administrador->getRawOriginal('password');

        config(['admin.password' => 'outra-senha-nao-deve-aplicar']);

        $this->seed(AdministradorSeeder::class);

        $this->assertSame(1, Usuario::query()->where('email', 'admin@example.test')->count());
        $this->assertSame($hashOriginal, $administrador->fresh()->getRawOriginal('password'));
        $this->assertTrue(Hash::check('senha-inicial-123456789', $administrador->fresh()->password));
    }

    public function test_seeder_recusa_promover_usuario_comum_existente(): void
    {
        $usuario = Usuario::factory()->create([
            'name' => 'Usuário Comum',
            'email' => 'admin@example.test',
            'administrador' => false,
        ]);
        $hashOriginal = $usuario->getRawOriginal('password');

        config([
            'admin.name' => 'Administrador',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'nao-deve-ser-usada',
        ]);

        try {
            $this->seed(AdministradorSeeder::class);
            $this->fail('O seeder deveria recusar o e-mail de um usuário comum.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('usuário comum', $exception->getMessage());
        }

        $usuario->refresh();

        $this->assertFalse($usuario->administrador);
        $this->assertSame('Usuário Comum', $usuario->name);
        $this->assertSame($hashOriginal, $usuario->getRawOriginal('password'));
        $this->assertSame(1, Usuario::query()->where('email', 'admin@example.test')->count());
    }
}
