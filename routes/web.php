<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProdutoController;
use App\Http\Controllers\Loja\CarrinhoController;
use App\Http\Controllers\Loja\CatalogoController;
use App\Http\Controllers\Loja\ProdutoController as ProdutoLojaController;
use App\Http\Controllers\ProfileController;
use App\Models\Produto;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogoController::class)->name('inicio');
Route::get('/produtos/{produto}', [ProdutoLojaController::class, 'show'])->name('loja.produtos.exibir');
Route::get('/carrinho', [CarrinhoController::class, 'index'])->name('carrinho');
Route::post('/carrinho/itens', [CarrinhoController::class, 'adicionarItem'])->name('loja.carrinho.itens.adicionar')->block(10, 10);
Route::patch('/carrinho/itens/{item}', [CarrinhoController::class, 'atualizarItem'])->name('loja.carrinho.itens.atualizar')->block(10, 10);
Route::delete('/carrinho/itens/{item}', [CarrinhoController::class, 'removerItem'])->name('loja.carrinho.itens.remover')->block(10, 10);

Route::redirect('/product-details', '/', 302);
Route::get('/products/{produto}', function (Produto $produto) {
    return redirect()->route('loja.produtos.exibir', $produto);
});
Route::redirect('/cart', '/carrinho');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'can:acessar-admin'])->group(function () {
    Route::get('/admin/painel', DashboardController::class)->name('admin.painel');
    Route::get('/admin/produtos', [ProdutoController::class, 'index'])->name('admin.produtos.index');
    Route::get('/admin/produtos/criar', [ProdutoController::class, 'create'])->name('admin.produtos.criar');
    Route::post('/admin/produtos', [ProdutoController::class, 'store'])->name('admin.produtos.salvar');
    Route::get('/admin/produtos/{produto}/editar', [ProdutoController::class, 'edit'])->name('admin.produtos.editar');
    Route::patch('/admin/produtos/{produto}', [ProdutoController::class, 'update'])->name('admin.produtos.atualizar');
    Route::delete('/admin/produtos/{produto}', [ProdutoController::class, 'destroy'])->name('admin.produtos.excluir');
    Route::redirect('/admin/dashboard', '/admin/painel');
    Route::redirect('/admin/products', '/admin/produtos');
    Route::redirect('/admin/products/create', '/admin/produtos/criar');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
