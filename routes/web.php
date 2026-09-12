<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CatalogController;
use App\Http\Controllers\Store\ProductController as StoreProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogController::class)->name('home');
Route::get('/products/{product}', [StoreProductController::class, 'show'])->name('store.products.show');
Route::redirect('/product-details', '/', 302);

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/items', [CartController::class, 'storeItem'])->name('store.cart.items.store')->block(10, 10);
Route::patch('/cart/items/{item}', [CartController::class, 'updateItem'])->name('store.cart.items.update')->block(10, 10);
Route::delete('/cart/items/{item}', [CartController::class, 'destroyItem'])->name('store.cart.items.destroy')->block(10, 10);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'can:access-admin'])->group(function () {
    Route::get('/admin/dashboard', DashboardController::class)->name('admin.dashboard');
    Route::get('/admin/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::get('/admin/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/admin/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::patch('/admin/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
