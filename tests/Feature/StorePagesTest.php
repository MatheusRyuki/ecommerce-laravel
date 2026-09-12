<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_displayed(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('No products are available in the store yet.'));
    }

    public function test_legacy_product_details_url_redirects_home(): void
    {
        $this->get('/product-details')->assertRedirect(route('home'));
    }

    public function test_cart_page_is_displayed(): void
    {
        $response = $this->get(route('cart'));

        $response->assertOk();
        $response->assertSee(__('Your cart is empty.'));
        $response->assertSee(__('Product total'));
        $response->assertSee('R$ 0,00');
        $response->assertDontSee('Sub total');
        $response->assertDontSee('$ 360.00');
    }
}
