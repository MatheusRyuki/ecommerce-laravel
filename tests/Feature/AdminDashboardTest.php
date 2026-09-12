<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_administrator_can_access_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee($admin->name);
        $response->assertSee($admin->email);
        $response->assertSee(__('Painel administrativo'));
    }

    public function test_public_registration_cannot_assign_admin_role(): void
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

    public function test_profile_update_cannot_assign_admin_role(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => true,
        ])->assertRedirect('/profile');

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_regular_user_does_not_see_admin_navigation_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Administração'))
            ->assertDontSee(__('Products'));
    }

    public function test_administrator_sees_admin_navigation_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Administração'));
    }
}
