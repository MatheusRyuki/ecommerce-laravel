<?php

namespace Tests\Feature;

use App\Cart\SessionCart;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorePresentationTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = [], array $paths = ['products/cover.png']): Product
    {
        Storage::fake('public');

        $product = Product::factory()->create(array_merge([
            'name' => 'Alert Bag',
            'sku' => 'ALERT-1',
            'price' => '49.90',
            'qty' => 15,
            'colors' => ['Red', 'Yellow'],
        ], $overrides));

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

    public function test_success_flash_appears_once_after_adding(): void
    {
        $product = $this->product();

        $this->post(route('store.cart.items.store'), [
            'product_id' => $product->id,
            'color' => 'Red',
            'quantity' => 1,
        ])->assertRedirect(route('cart'));

        $this->get(route('cart'))
            ->assertSee(__('The product was added to the cart.'))
            ->assertSee('alert-success', false)
            ->assertSee(__('Close'), false);

        $this->get(route('cart'))
            ->assertDontSee(__('The product was added to the cart.'));
    }

    public function test_quantity_error_is_scoped_to_the_affected_line(): void
    {
        $product = $this->product(['qty' => 5]);

        $this->post(route('store.cart.items.store'), [
            'product_id' => $product->id,
            'color' => 'Red',
            'quantity' => 3,
        ]);
        $this->post(route('store.cart.items.store'), [
            'product_id' => $product->id,
            'color' => 'Yellow',
            'quantity' => 1,
        ]);

        $redId = session(SessionCart::SESSION_KEY)[0]['id'];
        $yellowId = session(SessionCart::SESSION_KEY)[1]['id'];

        $response = $this->from(route('cart'))
            ->patch(route('store.cart.items.update', $redId), ['quantity' => 5]);

        $response->assertSessionHasErrors('cart_items.'.$redId)
            ->assertSessionDoesntHaveErrors('cart_items.'.$yellowId)
            ->assertSessionDoesntHaveErrors('quantity');

        $this->followingRedirects()
            ->from(route('cart'))
            ->patch(route('store.cart.items.update', $redId), ['quantity' => 5])
            ->assertSee(__('The requested quantity exceeds the available stock.'))
            ->assertSee('id="cart-qty-form-'.$redId.'"', false)
            ->assertSee('id="cart-qty-form-'.$yellowId.'"', false);
    }

    public function test_missing_image_uses_fallback_without_recursive_onerror(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['name' => 'Broken Cover', 'sku' => 'BRK-1']);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/missing.png',
            'position' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('No cover'))
            ->assertDontSee('products/missing.png', false);

        $this->get(route('store.products.show', $product))
            ->assertOk()
            ->assertSee(__('No cover'))
            ->assertDontSee('this.onerror=null', false);
    }

    public function test_gallery_renders_contain_photos_for_multiple_images(): void
    {
        $product = $this->product([], ['products/a.jpg', 'products/b.jpg']);

        $this->get(route('store.products.show', $product))
            ->assertOk()
            ->assertSee('store-photo__img', false)
            ->assertSee('this.onerror=null', false)
            ->assertSee(__('Thumbnail').' 2')
            ->assertSee('slider-navFive', false);
    }
}
