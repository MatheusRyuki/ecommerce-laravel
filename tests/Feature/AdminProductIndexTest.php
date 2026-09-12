<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_administrator_sees_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(__('No products have been registered yet.'))
            ->assertSee(route('admin.products.create'), false)
            ->assertSee(__('Create Product'));
    }

    public function test_administrator_sees_product_data_price_sku_and_cover(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $coverPath = 'products/cover-demo.png';
        Storage::disk('public')->put($coverPath, UploadedFile::fake()->image('cover-demo.png')->get());

        $product = Product::factory()->create([
            'name' => 'Bolsa Demo Curso',
            'sku' => 'DEMO-0001',
            'price' => '49.90',
            'qty' => 12,
            'colors' => ['Red', 'Yellow'],
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => $coverPath,
            'position' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Bolsa Demo Curso');
        $response->assertSee('DEMO-0001');
        $response->assertSee('R$ 49,90');
        $response->assertSee('12');
        $response->assertSee('Red');
        $response->assertSee('Yellow');
        $response->assertSee(Storage::disk('public')->url($coverPath), false);
        $response->assertDontSee($product->description, false);
    }

    public function test_products_are_ordered_and_paginated_sixteen_items(): void
    {
        $admin = User::factory()->admin()->create();
        $now = now();

        foreach (range(1, 16) as $index) {
            Product::factory()->create([
                'name' => 'Listed Product '.$index,
                'sku' => sprintf('PAGE-%02d', $index),
                'created_at' => $now->copy()->subMinutes(16 - $index),
                'updated_at' => $now->copy()->subMinutes(16 - $index),
            ]);
        }

        $firstPage = $this->actingAs($admin)->get(route('admin.products.index'));
        $firstPage->assertOk();
        $firstPage->assertSee('PAGE-16');
        $firstPage->assertSee('PAGE-02');
        $firstPage->assertDontSee('PAGE-01');
        $firstPage->assertSee('page=2', false);

        $secondPage = $this->actingAs($admin)->get(route('admin.products.index', ['page' => 2]));
        $secondPage->assertOk();
        $secondPage->assertSee('Listed Product 1');
        $secondPage->assertSee('PAGE-01');
        $secondPage->assertDontSee('PAGE-16');
    }

    public function test_successful_store_redirects_to_index_with_status_and_new_product_first(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Product::factory()->create([
            'name' => 'Older Product',
            'sku' => 'OLD-1',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'images' => [UploadedFile::fake()->image('cover.png', 20, 20)],
                'name' => 'Fresh Product',
                'price' => '10.00',
                'colors' => ['Green'],
                'short_description' => 'Newest item',
                'qty' => 3,
                'sku' => 'fresh-1',
                'description' => '<p>Newest item</p>',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'product-created');

        $html = $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(__('Product created successfully.'))
            ->assertSee('Fresh Product')
            ->assertSee('Older Product')
            ->getContent();

        $this->assertTrue(
            strpos($html, 'Fresh Product') < strpos($html, 'Older Product'),
            'The newly created product should appear before older products.',
        );
    }

    public function test_administrator_sees_products_link_in_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Products'))
            ->assertSee(route('admin.products.index'), false);
    }

    public function test_regular_user_does_not_see_products_navigation_link(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.products.index'), false);
    }

    public function test_missing_cover_uses_fallback(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'No Image Product']);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(__('No cover'))
            ->assertSee('No Image Product');
    }
}
