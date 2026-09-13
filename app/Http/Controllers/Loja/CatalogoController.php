<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(): View
    {
        $products = Product::query()
            ->with('images')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('store.home', [
            'products' => $products,
        ]);
    }
}
