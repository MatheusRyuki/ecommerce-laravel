<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ProductCreator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdminProductStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, UploadedFile>|null  $images
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(?array $images = null, array $overrides = []): array
    {
        return array_merge([
            'images' => $images ?? [
                UploadedFile::fake()->image('cover.png', 20, 20),
                UploadedFile::fake()->image('side.jpg', 20, 20),
            ],
            'name' => 'Demo Canvas Tote',
            'price' => '19.90',
            'colors' => ['Red', 'Blue'],
            'short_description' => 'A small tote for study demos.',
            'qty' => 7,
            'sku' => 'tote-001',
            'description' => '<p>Soft <strong>canvas</strong> tote.</p>',
        ], $overrides);
    }

    public function test_administrator_can_store_a_product_with_two_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), $this->validPayload());

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'product-created');

        $product = Product::query()->where('sku', 'TOTE-001')->first();
        $this->assertNotNull($product);
        $this->assertSame('Demo Canvas Tote', $product->name);
        $this->assertSame('19.90', $product->price);
        $this->assertSame(['Red', 'Blue'], $product->colors);
        $this->assertSame(7, $product->qty);

        $this->assertCount(2, $product->images);
        $this->assertSame(0, $product->images[0]->position);
        $this->assertSame(1, $product->images[1]->position);
        Storage::disk('public')->assertExists($product->images[0]->path);
        Storage::disk('public')->assertExists($product->images[1]->path);
        $this->assertSame($product->images[0]->path, $product->coverImage()?->path);
    }

    public function test_sku_keeps_leading_zeros_as_text(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->validPayload(overrides: [
            'sku' => '007abc',
        ]))->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['sku' => '007ABC']);
        $this->assertSame('007ABC', Product::query()->value('sku'));
    }

    public function test_duplicate_sku_is_rejected_after_normalization(): void
    {
        Storage::fake('public');
        Product::factory()->create(['sku' => 'ABC-1']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->validPayload(overrides: [
                'sku' => ' abc-1 ',
            ]))
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('sku');

        $this->assertSame(1, Product::query()->count());
        $this->assertSame(0, ProductImage::query()->count());
    }

    public function test_required_fields_numeric_limits_and_invalid_colors_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors(['images', 'name', 'price', 'colors', 'short_description', 'qty', 'sku', 'description']);

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->validPayload(overrides: [
                'price' => '100.999',
                'qty' => -1,
                'colors' => ['Purple'],
            ]))
            ->assertSessionHasErrors(['price', 'qty', 'colors.0']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload(overrides: [
                'price' => '100000000',
            ]))
            ->assertSessionHasErrors('price');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload(overrides: [
                'description' => '<p><br></p>',
            ]))
            ->assertSessionHasErrors('description');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_invalid_images_are_rejected_without_partial_records(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload([]))
            ->assertSessionHasErrors('images');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload([
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
                UploadedFile::fake()->image('e.jpg'),
                UploadedFile::fake()->image('f.jpg'),
            ]))
            ->assertSessionHasErrors('images');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload([
                UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('images.0');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload([
                UploadedFile::fake()->image('huge.jpg')->size(2049),
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, Product::query()->count());
        $this->assertSame(0, ProductImage::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_description_is_sanitized_and_allowed_markup_is_kept(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->validPayload(overrides: [
            'sku' => 'SAFE-1',
            'description' => '<p>Safe <strong>bold</strong> <em>and</em> <a href="https://example.test">link</a><script>alert(1)</script></p><a href="javascript:alert(1)">bad</a>',
        ]))->assertRedirect(route('admin.products.index'));

        $description = Product::query()->where('sku', 'SAFE-1')->value('description');
        $this->assertStringContainsString('<strong>bold</strong>', $description);
        $this->assertStringContainsString('<em>and</em>', $description);
        $this->assertStringContainsString('https://example.test', $description);
        $this->assertStringNotContainsString('<script', $description);
        $this->assertStringNotContainsString('javascript:', $description);
    }

    public function test_storage_failure_rolls_back_records_and_deletes_new_files(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('products/kept-should-be-deleted.jpg');
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with('products/kept-should-be-deleted.jpg')->andReturn(true);

        $this->app->instance(ProductCreator::class, new ProductCreator($disk));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->validPayload())
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('images');

        $this->assertSame(0, Product::query()->count());
        $this->assertSame(0, ProductImage::query()->count());
    }

    public function test_guest_cannot_store_a_product(): void
    {
        $this->post(route('admin.products.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertSame(0, Product::query()->count());
    }

    public function test_regular_user_cannot_store_a_product(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->post(route('admin.products.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, Product::query()->count());
    }
}
