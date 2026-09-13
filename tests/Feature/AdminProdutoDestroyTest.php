<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ProductDeleter;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminProductDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $paths
     */
    private function productWithImages(array $overrides = [], array $paths = ['products/cover.png']): Product
    {
        $product = Product::factory()->create($overrides);

        foreach (array_values($paths) as $position => $path) {
            Storage::disk('public')->put($path, 'image-'.$position);
            ProductImage::factory()->create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => $position,
            ]);
        }

        return $product->refresh()->load('images');
    }

    public function test_guest_cannot_delete_a_product(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_regular_user_cannot_delete_a_product(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => false]);
        $product = $this->productWithImages();

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_administrator_can_delete_a_product_with_multiple_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages(['name' => 'Temp Delete', 'sku' => 'TEMP-DEL-1'], [
            'products/temp-a.png',
            'products/temp-b.png',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.products.index'))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'product-deleted');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing('products/temp-a.png');
        Storage::disk('public')->assertMissing('products/temp-b.png');
    }

    public function test_deleting_a_product_does_not_affect_another_product_or_its_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $keep = $this->productWithImages(['sku' => 'KEEP-1'], ['products/keep.png']);
        $remove = $this->productWithImages(['sku' => 'GONE-1'], ['products/gone.png']);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $remove))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['id' => $keep->id, 'sku' => 'KEEP-1']);
        $this->assertDatabaseHas('product_images', ['product_id' => $keep->id, 'path' => 'products/keep.png']);
        Storage::disk('public')->assertExists('products/keep.png');
        Storage::disk('public')->assertMissing('products/gone.png');
    }

    public function test_missing_product_and_repeated_delete_return_not_found(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages();
        $id = $product->id;

        $this->actingAs($admin)
            ->delete('/admin/products/99999')
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->delete('/admin/products/'.$id)
            ->assertNotFound();
    }

    public function test_get_request_does_not_delete_a_product(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages();

        $this->actingAs($admin)
            ->get('/admin/products/'.$product->id)
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_delete_succeeds_when_one_image_file_is_already_missing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages(['sku' => 'MISS-1'], [
            'products/exists.png',
            'products/already-gone.png',
        ]);
        Storage::disk('public')->delete('products/already-gone.png');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'product-deleted');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('products/exists.png');
    }

    public function test_database_failure_preserves_records_and_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages(['sku' => 'FAIL-DB']);

        Product::deleting(function (): void {
            throw new RuntimeException('Forced database failure.');
        });

        try {
            $this->actingAs($admin)
                ->delete(route('admin.products.destroy', $product));
        } finally {
            Product::flushEventListeners();
        }

        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'FAIL-DB']);
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_physical_delete_failure_after_commit_keeps_database_deleted_and_tries_remaining_files(): void
    {
        Storage::fake('public');
        Log::spy();
        $product = $this->productWithImages(['sku' => 'PEND-1'], [
            'products/fail.png',
            'products/ok.png',
        ]);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('exists')->with('products/fail.png')->once()->andReturn(true);
        $disk->shouldReceive('delete')->with('products/fail.png')->once()->andReturn(false);
        $disk->shouldReceive('exists')->with('products/ok.png')->once()->andReturn(true);
        $disk->shouldReceive('delete')->with('products/ok.png')->once()->andReturn(true);

        $this->app->instance(ProductDeleter::class, new ProductDeleter($disk));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'product-deleted-with-pending-cleanup');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_deleting_the_last_product_shows_empty_state_on_the_first_page(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages(['name' => 'Only Product', 'sku' => 'ONLY-1']);

        $this->actingAs($admin)
            ->from(route('admin.products.index', ['page' => 2]))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(__('Product deleted successfully.'))
            ->assertSee(__('No products have been registered yet.'))
            ->assertDontSee('Only Product')
            ->assertDontSee('page=2', false);
    }

    public function test_index_includes_delete_confirmation_for_the_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Listed For Delete',
            'sku' => 'DEL-UI-1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(__('Delete'))
            ->assertSee('Listed For Delete')
            ->assertSee('DEL-UI-1')
            ->assertSee(route('admin.products.destroy', $product), false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="DELETE"', false);
    }
}
