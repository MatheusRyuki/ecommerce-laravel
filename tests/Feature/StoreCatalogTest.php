<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $paths
     */
    private function productWithImages(array $overrides = [], array $paths = ['products/cover.png']): Product
    {
        $product = Product::factory()->create($overrides);

        foreach (array_values($paths) as $position => $path) {
            Storage::disk('public')->put($path, 'img-'.$position);
            ProductImage::factory()->create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => $position,
            ]);
        }

        return $product->refresh()->load('images');
    }

    public function test_catalog_and_details_are_public(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages(['name' => 'Public Bag']);

        $this->get(route('home'))->assertOk()->assertSee('Public Bag');
        $this->get(route('store.products.show', $product))->assertOk()->assertSee('Public Bag');
    }

    public function test_catalog_shows_empty_state(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('No products are available in the store yet.'))
            ->assertDontSee('Smart Watch');
    }

    public function test_catalog_shows_real_data_price_cover_and_detail_links(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages([
            'name' => 'Bolsa Demo Editada',
            'sku' => 'DEMO-0001',
            'price' => '49.90',
            'qty' => 15,
        ], ['products/demo-cover.png']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Bolsa Demo Editada')
            ->assertSee('R$ 49,90')
            ->assertSee(Storage::disk('public')->url('products/demo-cover.png'), false)
            ->assertSee(route('store.products.show', $product), false)
            ->assertDontSee(route('store.products.show', ['product' => $product->id + 99]), false);
    }

    public function test_catalog_orders_and_paginates_thirteen_products(): void
    {
        $now = now();

        foreach (range(1, 13) as $index) {
            Product::factory()->create([
                'name' => sprintf('Vitrine %02d', $index),
                'sku' => sprintf('CAT-%02d', $index),
                'created_at' => $now->copy()->subMinutes(13 - $index),
                'updated_at' => $now->copy()->subMinutes(13 - $index),
            ]);
        }

        $firstPage = $this->get(route('home'));
        $firstPage->assertOk();
        $firstPage->assertSee('Vitrine 13');
        $firstPage->assertSee('Vitrine 02');
        $firstPage->assertDontSee('Vitrine 01');
        $firstPage->assertSee('page=2', false);

        $this->get(route('home', ['page' => 2]))
            ->assertOk()
            ->assertSee('Vitrine 01')
            ->assertDontSee('Vitrine 13');
    }

    public function test_two_products_have_isolated_details_and_images(): void
    {
        Storage::fake('public');
        $first = $this->productWithImages([
            'name' => 'First Isolated',
            'sku' => 'ISO-1',
            'description' => '<p>First body</p>',
        ], ['products/first.png']);
        $second = $this->productWithImages([
            'name' => 'Second Isolated',
            'sku' => 'ISO-2',
            'description' => '<p>Second body</p>',
        ], ['products/second.png']);

        $this->get(route('store.products.show', $first))
            ->assertOk()
            ->assertSee('First Isolated')
            ->assertSee('ISO-1')
            ->assertSee('First body')
            ->assertSee(Storage::disk('public')->url('products/first.png'), false)
            ->assertDontSee('Second Isolated')
            ->assertDontSee('products/second.png');

        $this->get(route('store.products.show', $second))
            ->assertOk()
            ->assertSee('Second Isolated')
            ->assertSee('ISO-2')
            ->assertDontSee('First Isolated')
            ->assertDontSee('products/first.png');
    }

    public function test_missing_and_deleted_products_return_not_found(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages();
        $id = $product->id;
        $product->delete();

        $this->get('/products/99999')->assertNotFound();
        $this->get('/products/'.$id)->assertNotFound();
    }

    public function test_gallery_handles_one_image_many_images_and_missing_file(): void
    {
        Storage::fake('public');
        $single = $this->productWithImages(['sku' => 'GAL-1'], ['products/only.png']);
        $many = $this->productWithImages(['sku' => 'GAL-2'], [
            'products/a.png',
            'products/b.png',
        ]);
        $missing = $this->productWithImages(['sku' => 'GAL-3'], ['products/gone.png']);
        Storage::disk('public')->delete('products/gone.png');

        $this->get(route('store.products.show', $single))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('products/only.png'), false)
            ->assertDontSee('slider-navFive');

        $manyResponse = $this->get(route('store.products.show', $many));
        $manyResponse->assertOk();
        $manyResponse->assertSee(Storage::disk('public')->url('products/a.png'), false);
        $manyResponse->assertSee(Storage::disk('public')->url('products/b.png'), false);
        $manyResponse->assertSee('slider-navFive', false);

        $this->get(route('store.products.show', $missing))
            ->assertOk()
            ->assertSee(__('No cover'));
    }

    public function test_zero_stock_product_is_visible_and_marked_unavailable(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages([
            'name' => 'Out of Stock Bag',
            'qty' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Out of Stock Bag')
            ->assertSee(__('Unavailable'));

        $this->get(route('store.products.show', $product))
            ->assertOk()
            ->assertSee(__('Unavailable'));
    }

    public function test_plain_text_is_escaped_and_rich_description_is_sanitized(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages([
            'name' => '<script>alert(1)</script>',
            'sku' => 'XSS-1',
            'short_description' => '<b>plain</b>',
            'description' => '<p>Safe <strong>bold</strong><script>alert(1)</script></p><a href="javascript:alert(1)">bad</a>',
        ]);

        $response = $this->get(route('store.products.show', $product));
        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertSee('<strong>bold</strong>', false);
        $response->assertDontSee('javascript:', false);
        $response->assertSee('&lt;b&gt;plain&lt;/b&gt;', false);
    }

    public function test_admin_changes_and_deletes_appear_on_the_catalog(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = $this->productWithImages(['name' => 'Before Store', 'sku' => 'SYNC-1']);

        $this->get(route('home'))->assertSee('Before Store');

        $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'name' => 'After Store',
            'price' => $product->price,
            'colors' => $product->colors,
            'short_description' => $product->short_description,
            'qty' => $product->qty,
            'sku' => $product->sku,
            'description' => $product->description,
        ])->assertRedirect(route('admin.products.index'));

        $this->get(route('home'))
            ->assertSee('After Store')
            ->assertDontSee('Before Store');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->get(route('home'))
            ->assertDontSee('After Store')
            ->assertSee(__('No products are available in the store yet.'));
    }

    public function test_legacy_product_details_url_redirects_to_the_catalog(): void
    {
        $this->get('/product-details')->assertRedirect(route('home'));
    }

    public function test_purchase_controls_are_disabled_on_the_storefront(): void
    {
        Storage::fake('public');
        $product = $this->productWithImages();

        $this->get(route('home'))
            ->assertSee(__('Add To Cart'))
            ->assertSee(route('store.products.show', $product), false)
            ->assertDontSee(route('store.cart.items.store'), false);

        $this->get(route('store.products.show', $product))
            ->assertSee(__('Buy Now'))
            ->assertSee('pe-none', false)
            ->assertSee(route('store.cart.items.store'), false);
    }
}
