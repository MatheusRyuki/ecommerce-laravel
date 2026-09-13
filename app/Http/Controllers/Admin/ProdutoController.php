<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductCreator;
use App\Services\ProductDeleter;
use App\Services\ProductUpdater;
use App\Support\ProductColors;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('images')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.products.index', [
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'colors' => ProductColors::all(),
        ]);
    }

    public function store(StoreProductRequest $request, ProductCreator $creator): RedirectResponse
    {
        try {
            $creator->create($request->validated(), $request->file('images', []));
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['sku' => __('This SKU is already in use.')])
                ->withInput();
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['images' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'product-created');
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.edit', [
            'product' => $product,
            'colors' => ProductColors::all(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductUpdater $updater): RedirectResponse
    {
        $attributes = $request->safe()->except(['images', 'remove_image_ids']);

        try {
            $updater->update(
                $product,
                $attributes,
                $request->newImageFiles(),
                $request->validated('remove_image_ids') ?? [],
            );
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['sku' => __('This SKU is already in use.')])
                ->withInput();
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['images' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'product-updated');
    }

    public function destroy(Product $product, ProductDeleter $deleter): RedirectResponse
    {
        $pending = $deleter->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('status', $pending === [] ? 'product-deleted' : 'product-deleted-with-pending-cleanup');
    }
}
