<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.products.create'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.products.create'))
            ->assertForbidden();
    }

    public function test_administrator_can_view_the_create_product_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.products.create'));

        $response->assertOk();
        $response->assertSee('name="images[]"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="price"', false);
        $response->assertSee('name="colors[]"', false);
        $response->assertSee('name="short_description"', false);
        $response->assertSee('name="qty"', false);
        $response->assertSee('name="sku"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee(__('Short Description'));
        $response->assertSee(__('Go Back'));
        $response->assertSee(route('admin.products.index'), false);
        $response->assertSee(route('admin.products.store'), false);
        $response->assertSee(__('Price (BRL)'));
        $response->assertDontSee('disabled', false);
    }

    public function test_administrator_sees_create_product_link_on_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.products.create'), false)
            ->assertSee(route('admin.products.index'), false)
            ->assertSee(__('Products'))
            ->assertSee(__('Create Product'));
    }
}
