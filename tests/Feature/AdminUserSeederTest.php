<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_cria_administrador_com_senha_hasheada(): void
    {
        config([
            'admin.name' => 'Administrador',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'senha-forte-de-teste-123',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@example.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_admin);
        $this->assertSame('Administrador', $admin->name);
        $this->assertNotSame('senha-forte-de-teste-123', $admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check('senha-forte-de-teste-123', $admin->password));
    }

    public function test_seeder_pode_rodar_de_novo_sem_duplicar_nem_trocar_senha(): void
    {
        config([
            'admin.name' => 'Administrador',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'senha-inicial-123456789',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@example.test')->first();
        $originalHash = $admin->getRawOriginal('password');

        config(['admin.password' => 'outra-senha-nao-deve-aplicar']);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@example.test')->count());
        $this->assertSame($originalHash, $admin->fresh()->getRawOriginal('password'));
        $this->assertTrue(Hash::check('senha-inicial-123456789', $admin->fresh()->password));
    }

    public function test_seeder_recusa_promover_usuario_comum_existente(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuário Comum',
            'email' => 'admin@example.test',
            'is_admin' => false,
        ]);
        $originalHash = $user->getRawOriginal('password');

        config([
            'admin.name' => 'Administrador',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'nao-deve-ser-usada',
        ]);

        try {
            $this->seed(AdminUserSeeder::class);
            $this->fail('O seeder deveria recusar o e-mail de um usuário comum.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('usuário comum', $exception->getMessage());
        }

        $user->refresh();

        $this->assertFalse($user->is_admin);
        $this->assertSame('Usuário Comum', $user->name);
        $this->assertSame($originalHash, $user->getRawOriginal('password'));
        $this->assertSame(1, User::query()->where('email', 'admin@example.test')->count());
    }
}
