<?php

namespace Tests\Feature;

use App\Cart\SessionCart;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreCartMutationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $colors
     */
    private function product(array $overrides = [], array $colors = ['Red', 'Yellow']): Product
    {
        Storage::fake('public');

        $product = Product::factory()->create(array_merge([
            'name' => 'Cart Bag',
            'sku' => 'CART-0001',
            'price' => '49.90',
            'qty' => 15,
            'colors' => $colors,
        ], $overrides));

        $path = 'products/cart-cover.png';
        Storage::disk('public')->put($path, 'img');
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => $path,
            'position' => 0,
        ]);

        return $product->refresh()->load('images');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function add(Product $product, array $overrides = []): void
    {
        $this->post(route('store.cart.items.store'), array_merge([
            'product_id' => $product->id,
            'color' => 'Red',
            'quantity' => 1,
        ], $overrides))->assertRedirect(route('cart'));
    }

    private function itemId(int $index = 0): string
    {
        return session(SessionCart::SESSION_KEY)[$index]['id'];
    }

    public function test_update_and_delete_work_in_the_current_session(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 2]);
        $id = $this->itemId();

        $this->from(route('cart'))
            ->patch(route('store.cart.items.update', $id), ['quantity' => 4])
            ->assertRedirect(route('cart'))
            ->assertSessionHas('status');

        $this->assertSame(4, session(SessionCart::SESSION_KEY)[0]['quantity']);

        $this->from(route('cart'))
            ->delete(route('store.cart.items.destroy', $id))
            ->assertRedirect(route('cart'));

        $this->assertSame([], session(SessionCart::SESSION_KEY));
    }

    public function test_unknown_item_and_other_session_return_404(): void
    {
        $product = $this->product();
        $this->add($product);
        $foreignId = $this->itemId();

        $this->patch(route('store.cart.items.update', (string) Str::uuid()), ['quantity' => 2])
            ->assertNotFound();
        $this->delete(route('store.cart.items.destroy', (string) Str::uuid()))
            ->assertNotFound();

        $this->flushSession();
        $other = $this->product(['sku' => 'CART-0002', 'name' => 'Other Bag'], ['Blue']);
        $this->withSession([
            SessionCart::SESSION_KEY => [[
                'id' => (string) Str::uuid(),
                'product_id' => $other->id,
                'color' => 'Blue',
                'quantity' => 1,
            ]],
        ])->patch(route('store.cart.items.update', $foreignId), ['quantity' => 3])
            ->assertNotFound();
    }

    public function test_removing_one_color_keeps_the_other_line(): void
    {
        $product = $this->product();
        $this->add($product, ['color' => 'Red', 'quantity' => 2]);
        $this->add($product, ['color' => 'Yellow', 'quantity' => 1]);
        $redId = $this->itemId(0);

        $this->delete(route('store.cart.items.destroy', $redId))->assertRedirect(route('cart'));

        $items = session(SessionCart::SESSION_KEY);
        $this->assertCount(1, $items);
        $this->assertSame('Yellow', $items[0]['color']);
        $this->assertSame(1, $items[0]['quantity']);
    }

    public function test_unavailable_and_deleted_products_can_be_removed(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 2]);
        $id = $this->itemId();
        $product->delete();

        $this->get(route('cart'))->assertSee(__('Unavailable item'));
        $this->delete(route('store.cart.items.destroy', $id))->assertRedirect(route('cart'));
        $this->get(route('cart'))
            ->assertSee(__('Your cart is empty.'))
            ->assertSee('R$ 0,00')
            ->assertSee('>0</b>', false);
    }

    public function test_patch_replaces_quantity_instead_of_adding(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 2]);
        $this->patch(route('store.cart.items.update', $this->itemId()), ['quantity' => 5])
            ->assertRedirect(route('cart'));

        $this->assertSame(5, session(SessionCart::SESSION_KEY)[0]['quantity']);
        $this->assertCount(1, session(SessionCart::SESSION_KEY));
    }

    public function test_stock_across_colors_rejects_increase_and_preserves_cart(): void
    {
        $product = $this->product(['qty' => 5]);
        $this->add($product, ['color' => 'Red', 'quantity' => 3]);
        $this->add($product, ['color' => 'Yellow', 'quantity' => 1]);
        $previous = session(SessionCart::SESSION_KEY);
        $redId = $previous[0]['id'];

        $this->from(route('cart'))
            ->patch(route('store.cart.items.update', $redId), ['quantity' => 5])
            ->assertSessionHasErrors('cart_items.'.$redId);

        $this->assertSame($previous, session(SessionCart::SESSION_KEY));
    }

    public function test_quantity_can_decrease_gradually_after_stock_shrinks(): void
    {
        $product = $this->product(['qty' => 10]);
        $this->add($product, ['quantity' => 8]);
        $id = $this->itemId();
        $product->update(['qty' => 5]);

        $this->get(route('cart'))
            ->assertSee(__('This item needs adjustment'))
            ->assertSee(__('The product total cannot be calculated until every item is available.'))
            ->assertDontSee(__('Product total'));

        $this->patch(route('store.cart.items.update', $id), ['quantity' => 7])
            ->assertRedirect(route('cart'));
        $this->assertSame(7, session(SessionCart::SESSION_KEY)[0]['quantity']);
        $this->get(route('cart'))->assertSee(__('This item needs adjustment'));

        $this->patch(route('store.cart.items.update', $id), ['quantity' => 5])
            ->assertRedirect(route('cart'));
        $this->get(route('cart'))
            ->assertDontSee(__('This item needs adjustment'))
            ->assertSee(__('Product total'))
            ->assertSee('R$ 249,50');

        $this->from(route('cart'))
            ->patch(route('store.cart.items.update', $id), ['quantity' => 6])
            ->assertSessionHasErrors('cart_items.'.$id);
        $this->assertSame(5, session(SessionCart::SESSION_KEY)[0]['quantity']);
    }

    public function test_zero_negative_and_fractional_quantities_are_rejected(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 2]);
        $id = $this->itemId();
        $previous = session(SessionCart::SESSION_KEY);

        $this->patch(route('store.cart.items.update', $id), ['quantity' => 0])->assertSessionHasErrors('cart_items.'.$id);
        $this->patch(route('store.cart.items.update', $id), ['quantity' => -1])->assertSessionHasErrors('cart_items.'.$id);
        $this->patch(route('store.cart.items.update', $id), ['quantity' => 1.5])->assertSessionHasErrors('cart_items.'.$id);
        $this->assertSame($previous, session(SessionCart::SESSION_KEY));
    }

    public function test_subtotals_and_total_use_exact_decimals_and_price_limits(): void
    {
        $product = $this->product(['price' => '49.90', 'qty' => 15]);
        $this->add($product, ['color' => 'Red', 'quantity' => 3]);
        $this->add($product, ['color' => 'Yellow', 'quantity' => 1]);

        $this->get(route('cart'))
            ->assertSee('R$ 149,70')
            ->assertSee('R$ 49,90')
            ->assertSee('R$ 199,60')
            ->assertSee(__('Product total'));

        $expensive = $this->product([
            'name' => 'Limit Bag',
            'sku' => 'LIM-1',
            'price' => '99999999.99',
            'qty' => 2,
            'colors' => ['Blue'],
        ], ['Blue']);
        $this->flushSession();
        $this->add($expensive, ['color' => 'Blue', 'quantity' => 2]);
        $this->get(route('cart'))->assertSee('R$ 199.999.999,98');
    }

    public function test_updated_database_price_is_used_in_totals(): void
    {
        $product = $this->product(['price' => '19.90']);
        $this->add($product, ['quantity' => 2]);
        $product->update(['price' => '29.90']);

        $this->get(route('cart'))
            ->assertSee('R$ 29,90')
            ->assertSee('R$ 59,80')
            ->assertDontSee('R$ 19,90')
            ->assertDontSee('R$ 39,80');
    }

    public function test_client_submitted_totals_are_ignored(): void
    {
        $product = $this->product(['price' => '49.90']);
        $this->add($product, ['quantity' => 2]);
        $id = $this->itemId();

        $this->patch(route('store.cart.items.update', $id), [
            'quantity' => 2,
            'price' => '0.01',
            'total' => '0.02',
        ])->assertRedirect(route('cart'));

        $this->get(route('cart'))
            ->assertSee('R$ 99,80')
            ->assertDontSee('R$ 0,02');
    }

    public function test_removing_last_item_shows_empty_cart_and_zero_total(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 3]);
        $this->delete(route('store.cart.items.destroy', $this->itemId()))->assertRedirect(route('cart'));

        $this->get(route('cart'))
            ->assertSee(__('Your cart is empty.'))
            ->assertSee('R$ 0,00')
            ->assertSee('>0</b>', false);
        $this->assertSame(15, $product->fresh()->qty);
    }

    public function test_mutations_do_not_change_product_stock(): void
    {
        $product = $this->product(['qty' => 15]);
        $this->add($product, ['quantity' => 2]);
        $this->patch(route('store.cart.items.update', $this->itemId()), ['quantity' => 4]);
        $this->delete(route('store.cart.items.destroy', $this->itemId()));

        $this->assertSame(15, $product->fresh()->qty);
    }

    public function test_get_does_not_update_or_delete(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 2]);
        $id = $this->itemId();

        $this->get('/cart/items/'.$id)->assertMethodNotAllowed();
        $this->assertSame(2, session(SessionCart::SESSION_KEY)[0]['quantity']);
    }

    public function test_unavailable_line_cannot_be_updated(): void
    {
        $product = $this->product();
        $this->add($product, ['quantity' => 2]);
        $id = $this->itemId();
        $previous = session(SessionCart::SESSION_KEY);
        $product->update(['qty' => 0]);

        $this->from(route('cart'))
            ->patch(route('store.cart.items.update', $id), ['quantity' => 1])
            ->assertSessionHasErrors('cart_items.'.$id);
        $this->assertSame($previous, session(SessionCart::SESSION_KEY));
    }
}
