<?php

namespace Tests\Feature;

use App\Cart\SessionCart;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StoreCartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $colors
     */
    private function product(array $overrides = [], array $colors = ['Red', 'Yellow'], array $paths = ['products/cart-cover.png']): Product
    {
        Storage::fake('public');

        $product = Product::factory()->create(array_merge([
            'name' => 'Cart Bag',
            'sku' => 'CART-0001',
            'price' => '49.90',
            'qty' => 15,
            'colors' => $colors,
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function addItem(Product $product, array $overrides = []): TestResponse
    {
        return $this->from(route('store.products.show', $product))
            ->post(route('store.cart.items.store'), array_merge([
                'product_id' => $product->id,
                'color' => 'Red',
                'quantity' => 1,
            ], $overrides));
    }

    public function test_empty_cart_is_public(): void
    {
        $this->get(route('cart'))
            ->assertOk()
            ->assertSee(__('Your cart is empty.'))
            ->assertSee(__('Continue Shopping'))
            ->assertSee(route('home'), false);
    }

    public function test_valid_add_persists_between_requests(): void
    {
        $product = $this->product();

        $this->addItem($product, ['quantity' => 2])
            ->assertRedirect(route('cart'))
            ->assertSessionHas('status');

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Cart Bag')
            ->assertSee('Red')
            ->assertSee('R$ 49,90')
            ->assertSee('value="2"', false)
            ->assertSee(route('store.products.show', $product), false)
            ->assertSee(Storage::disk('public')->url('products/cart-cover.png'), false)
            ->assertSee(__('The product was added to the cart.'));

        $this->get(route('cart'))->assertSee('Cart Bag')->assertSee('>2</b>', false);
    }

    public function test_same_combination_accumulates_and_colors_are_separate_lines(): void
    {
        $product = $this->product();

        $this->addItem($product, ['color' => 'Red', 'quantity' => 2]);
        $this->addItem($product, ['color' => 'Red', 'quantity' => 1]);
        $this->addItem($product, ['color' => 'Yellow', 'quantity' => 1]);

        $items = session(SessionCart::SESSION_KEY);
        $this->assertCount(2, $items);
        $this->assertSame(3, $items[0]['quantity']);
        $this->assertSame('Red', $items[0]['color']);
        $this->assertSame(1, $items[1]['quantity']);
        $this->assertSame('Yellow', $items[1]['color']);
        $this->assertNotSame($items[0]['id'], $items[1]['id']);

        $this->get(route('cart'))
            ->assertSee('Red')
            ->assertSee('Yellow')
            ->assertSee('>4</b>', false);
    }

    public function test_stock_limit_includes_quantities_of_all_colors(): void
    {
        $product = $this->product(['qty' => 5]);

        $this->addItem($product, ['color' => 'Red', 'quantity' => 3])->assertRedirect(route('cart'));
        $this->addItem($product, ['color' => 'Yellow', 'quantity' => 3])
            ->assertRedirect(route('store.products.show', $product))
            ->assertSessionHasErrors('cart');

        $items = session(SessionCart::SESSION_KEY);
        $this->assertCount(1, $items);
        $this->assertSame(3, $items[0]['quantity']);
        $this->assertSame('Red', $items[0]['color']);
    }

    public function test_invalid_quantity_color_and_missing_product_are_rejected(): void
    {
        $product = $this->product();

        $this->addItem($product, ['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->addItem($product, ['quantity' => 1.5])->assertSessionHasErrors('quantity');
        $this->addItem($product, ['color' => 'Blue'])->assertSessionHasErrors('cart');
        $this->from(route('home'))
            ->post(route('store.cart.items.store'), [
                'product_id' => 99999,
                'color' => 'Red',
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertSame([], session(SessionCart::SESSION_KEY, []));
    }

    public function test_rejected_add_preserves_previous_cart(): void
    {
        $product = $this->product(['qty' => 4]);

        $this->addItem($product, ['quantity' => 2])->assertRedirect(route('cart'));
        $previous = session(SessionCart::SESSION_KEY);

        $this->addItem($product, ['quantity' => 5])
            ->assertSessionHasErrors('cart');

        $this->assertSame($previous, session(SessionCart::SESSION_KEY));
    }

    public function test_client_price_does_not_change_displayed_price(): void
    {
        $product = $this->product(['price' => '49.90']);

        $this->addItem($product, ['price' => '0.01'])->assertRedirect(route('cart'));

        $this->get(route('cart'))
            ->assertSee('R$ 49,90')
            ->assertDontSee('R$ 0,01');
    }

    public function test_carts_from_independent_sessions_stay_separated(): void
    {
        $first = $this->product(['name' => 'Session One Bag', 'sku' => 'S1']);
        $second = $this->product(['name' => 'Session Two Bag', 'sku' => 'S2'], ['Blue'], ['products/other.png']);

        $this->withSession([
            SessionCart::SESSION_KEY => [[
                'id' => 'line-a',
                'product_id' => $first->id,
                'color' => 'Red',
                'quantity' => 1,
            ]],
        ])->get(route('cart'))
            ->assertSee('Session One Bag')
            ->assertDontSee('Session Two Bag');

        $this->withSession([
            SessionCart::SESSION_KEY => [[
                'id' => 'line-b',
                'product_id' => $second->id,
                'color' => 'Blue',
                'quantity' => 2,
            ]],
        ])->get(route('cart'))
            ->assertSee('Session Two Bag')
            ->assertDontSee('Session One Bag');
    }

    public function test_adding_does_not_change_product_stock(): void
    {
        $product = $this->product(['qty' => 15]);

        $this->addItem($product, ['quantity' => 3]);

        $this->assertSame(15, $product->fresh()->qty);
    }

    public function test_get_does_not_add_items(): void
    {
        $product = $this->product();

        $this->get('/cart/items?product_id='.$product->id.'&color=Red&quantity=1')
            ->assertMethodNotAllowed();

        $this->assertSame([], session(SessionCart::SESSION_KEY, []));
    }

    public function test_authenticated_guest_can_use_the_same_session_cart(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->addItem($product, ['quantity' => 1])
            ->assertRedirect(route('cart'));

        $this->actingAs($user)
            ->get(route('cart'))
            ->assertOk()
            ->assertSee('Cart Bag');
    }

    public function test_catalog_changes_after_add_are_flagged_without_silent_quantity_changes(): void
    {
        $product = $this->product(['qty' => 10, 'price' => '19.90', 'colors' => ['Red', 'Yellow']]);

        $this->addItem($product, ['color' => 'Red', 'quantity' => 4]);
        $this->addItem($product, ['color' => 'Yellow', 'quantity' => 2]);

        $product->update([
            'qty' => 3,
            'colors' => ['Yellow'],
            'price' => '29.90',
        ]);

        $response = $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Cart Bag')
            ->assertSee('R$ 29,90')
            ->assertDontSee('R$ 19,90')
            ->assertSee(__('This item needs adjustment'))
            ->assertSee('value="4"', false)
            ->assertSee('value="2"', false);

        $this->assertCount(2, session(SessionCart::SESSION_KEY));
        $this->assertSame(4, session(SessionCart::SESSION_KEY)[0]['quantity']);
        $this->assertSame(2, session(SessionCart::SESSION_KEY)[1]['quantity']);
        $this->assertTrue($response->baseResponse->isOk());

        $product->update(['qty' => 0]);

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee(__('Unavailable'))
            ->assertSee('value="4"', false);

        $productId = $product->id;
        $product->delete();

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee(__('Unavailable item'))
            ->assertDontSee(route('store.products.show', $productId), false)
            ->assertDontSee('R$ 29,90')
            ->assertSee('—');

        $this->assertCount(2, session(SessionCart::SESSION_KEY));
    }

    public function test_vitrine_add_to_cart_points_to_details(): void
    {
        $product = $this->product(['name' => 'Vitrine Link Bag']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('store.products.show', $product), false)
            ->assertDontSee(route('store.cart.items.store'), false);
    }

    public function test_details_enable_add_to_cart_for_available_products(): void
    {
        $product = $this->product();

        $this->get(route('store.products.show', $product))
            ->assertOk()
            ->assertSee(route('store.cart.items.store'), false)
            ->assertSee('name="quantity"', false)
            ->assertSee('name="color"', false)
            ->assertSee(__('Select a color'));
    }
}
