<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ProductUpdater;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminProductUpdateTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name' => $product->name,
            'price' => $product->price,
            'colors' => $product->colors,
            'short_description' => $product->short_description,
            'qty' => $product->qty,
            'sku' => $product->sku,
            'description' => $product->description,
        ], $overrides);
    }

    public function test_guest_is_redirected_from_edit_and_update(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.edit', $product))
            ->assertRedirect(route('login'));

        $this->patch(route('admin.products.update', $product), [])
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_edit_and_update(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [])
            ->assertForbidden();
    }

    public function test_missing_product_returns_not_found_for_administrator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/products/99999/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch('/admin/products/99999', [])
            ->assertNotFound();
    }

    public function test_edit_form_is_filled_and_sku_can_be_kept(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages([
            'name' => 'Bolsa Demo Curso',
            'sku' => 'DEMO-0001',
            'price' => '49.90',
            'qty' => 12,
            'colors' => ['Red', 'Yellow'],
            'short_description' => 'Produto ficticio',
            'description' => '<p>Bolsa de <strong>estudo</strong>.</p>',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Bolsa Demo Curso')
            ->assertSee('DEMO-0001')
            ->assertSee('value="49.90"', false)
            ->assertSee('Produto ficticio')
            ->assertSee('estudo')
            ->assertSee(__('Cover'))
            ->assertSee(__('Save Changes'))
            ->assertSee('name="_method"', false)
            ->assertSee('value="PATCH"', false);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'sku' => ' demo-0001 ',
                'qty' => 20,
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'product-updated');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'DEMO-0001',
            'qty' => 20,
        ]);
        $this->assertSame(1, $product->images()->count());
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_duplicate_sku_from_another_product_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['sku' => 'TAKEN-1']);
        $product = $this->productWithImages(['sku' => 'KEEP-1']);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'sku' => ' taken-1 ',
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('sku');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'KEEP-1']);
    }

    public function test_invalid_update_preserves_previous_data_and_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages([
            'name' => 'Original Name',
            'sku' => 'ORIG-1',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'name' => '',
                'price' => '12.999',
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors(['name', 'price'])
            ->assertSessionHasInput('name', '');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Name',
            'sku' => 'ORIG-1',
        ]);
        Storage::disk('public')->assertExists('products/cover.png');
    }

    public function test_update_without_new_files_keeps_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages([], ['products/a.png', 'products/b.png']);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'name' => 'Renamed',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh()->load('images');
        $this->assertSame('Renamed', $product->name);
        $this->assertSame(['products/a.png', 'products/b.png'], $product->images->pluck('path')->all());
        $this->assertSame([0, 1], $product->images->pluck('position')->all());
    }

    public function test_images_can_be_added_removed_and_cover_changes(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages([], [
            'products/cover-old.png',
            'products/side.png',
        ]);
        $coverId = $product->images[0]->id;

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'remove_image_ids' => [$coverId],
                'images' => [UploadedFile::fake()->image('extra.jpg', 20, 20)],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh()->load('images');
        $this->assertCount(2, $product->images);
        $this->assertSame('products/side.png', $product->images[0]->path);
        $this->assertSame(0, $product->images[0]->position);
        $this->assertSame(1, $product->images[1]->position);
        $this->assertStringStartsWith('products/', $product->images[1]->path);
        Storage::disk('public')->assertMissing('products/cover-old.png');
        Storage::disk('public')->assertExists('products/side.png');
        Storage::disk('public')->assertExists($product->images[1]->path);
    }

    public function test_final_image_count_cannot_be_zero_or_above_five(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $single = $this->productWithImages(['sku' => 'ONE-1'], ['products/only.png']);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $single))
            ->patch(route('admin.products.update', $single), $this->payload($single, [
                'remove_image_ids' => [$single->images[0]->id],
            ]))
            ->assertSessionHasErrors('images');

        $this->assertSame(1, $single->images()->count());
        Storage::disk('public')->assertExists('products/only.png');

        $full = $this->productWithImages(['sku' => 'FIVE-1'], [
            'products/1.png',
            'products/2.png',
            'products/3.png',
            'products/4.png',
            'products/5.png',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $full))
            ->patch(route('admin.products.update', $full), $this->payload($full, [
                'images' => [UploadedFile::fake()->image('sixth.jpg')],
            ]))
            ->assertSessionHasErrors('images');

        $this->assertSame(5, $full->images()->count());
        Storage::disk('public')->assertExists('products/1.png');
    }

    public function test_removing_another_products_image_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages(['sku' => 'OWN-1'], ['products/own.png']);
        $other = $this->productWithImages(['sku' => 'OTH-1'], ['products/other.png']);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'remove_image_ids' => [$other->images[0]->id],
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('remove_image_ids.0');

        $this->assertDatabaseHas('product_images', [
            'id' => $product->images[0]->id,
            'path' => 'products/own.png',
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $other->images[0]->id,
            'path' => 'products/other.png',
        ]);
    }

    public function test_storage_failure_keeps_old_files_and_removes_new_ones(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages(['name' => 'Untouched'], ['products/keep.png']);

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('products/tmp-new.png');
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with('products/tmp-new.png')->andReturn(true);

        $this->app->instance(ProductUpdater::class, new ProductUpdater($disk));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'name' => 'Should Not Save',
                'images' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.jpg'),
                ],
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('images');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Untouched']);
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id, 'path' => 'products/keep.png']);
        Storage::disk('public')->assertExists('products/keep.png');
    }

    public function test_failed_physical_delete_after_commit_does_not_undo_update(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages(['name' => 'Before'], ['products/remove-me.png']);
        $removeId = $product->images[0]->id;

        Log::spy();
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('products/brand-new.png');
        $disk->shouldReceive('delete')->once()->with('products/remove-me.png')->andReturn(false);

        $this->app->instance(ProductUpdater::class, new ProductUpdater($disk));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->payload($product, [
                'name' => 'After Commit',
                'remove_image_ids' => [$removeId],
                'images' => [UploadedFile::fake()->image('brand-new.png')],
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'product-updated');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'After Commit']);
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id, 'path' => 'products/brand-new.png']);
        $this->assertDatabaseMissing('product_images', ['id' => $removeId]);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_index_includes_edit_link(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(__('Edit'))
            ->assertSee(route('admin.products.edit', $product), false);
    }
}
