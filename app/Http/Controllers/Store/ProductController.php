<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product->load('images');

        return view('store.product-details', [
            'product' => $product,
        ]);
    }
}
