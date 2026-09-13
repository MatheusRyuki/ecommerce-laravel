<?php

use App\Http\Controllers\Admin\CategoriaController as AdminCategoriaController;
use App\Http\Controllers\Admin\CupomController as AdminCupomController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EstoqueController;
use App\Http\Controllers\Admin\FaixaFreteController;
use App\Http\Controllers\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Admin\ProdutoController;
use App\Http\Controllers\Admin\UsuarioController as AdminUsuarioController;
use App\Http\Controllers\Conta\EnderecoController;
use App\Http\Controllers\Conta\FavoritoController;
use App\Http\Controllers\Conta\PedidoController as ContaPedidoController;
use App\Http\Controllers\Loja\CarrinhoController;
use App\Http\Controllers\Loja\CatalogoController;
use App\Http\Controllers\Loja\CategoriaController as LojaCategoriaController;
use App\Http\Controllers\Loja\CheckoutController;
use App\Http\Controllers\Loja\ProdutoController as ProdutoLojaController;
use App\Http\Controllers\PerfilController;
use App\Models\Produto;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogoController::class)->name('inicio');
Route::get('/categorias/{categoria:slug}', [LojaCategoriaController::class, 'exibir'])->name('loja.categorias.exibir');
Route::get('/produtos/{produto}/favoritos/entrar', function (Produto $produto) {
    session(['url.intended' => route('loja.produtos.exibir', $produto)]);

    return redirect()->route('login');
})->name('conta.favoritos.convidado');
Route::get('/produtos/{produto}', [ProdutoLojaController::class, 'exibir'])->name('loja.produtos.exibir');
Route::get('/carrinho', [CarrinhoController::class, 'exibir'])->name('carrinho');
Route::post('/carrinho/itens', [CarrinhoController::class, 'adicionarItem'])->name('loja.carrinho.itens.adicionar')->block(10, 10);
Route::patch('/carrinho/itens/{item}', [CarrinhoController::class, 'atualizarItem'])->name('loja.carrinho.itens.atualizar')->block(10, 10);
Route::delete('/carrinho/itens/{item}', [CarrinhoController::class, 'removerItem'])->name('loja.carrinho.itens.remover')->block(10, 10);

Route::redirect('/product-details', '/', 302);
Route::get('/products/{produto}', function (Produto $produto) {
    return redirect()->route('loja.produtos.exibir', $produto);
});
Route::redirect('/cart', '/carrinho');

Route::get('/dashboard', function () {
    return view('painel');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'can:acessar-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/painel', DashboardController::class)->name('painel');
    Route::get('/produtos', [ProdutoController::class, 'listar'])->name('produtos.listar');
    Route::get('/produtos/criar', [ProdutoController::class, 'criar'])->name('produtos.criar');
    Route::post('/produtos', [ProdutoController::class, 'salvar'])->name('produtos.salvar');
    Route::get('/produtos/{produto}/editar', [ProdutoController::class, 'editar'])->name('produtos.editar');
    Route::patch('/produtos/{produto}', [ProdutoController::class, 'atualizar'])->name('produtos.atualizar');
    Route::delete('/produtos/{produto}', [ProdutoController::class, 'excluir'])->name('produtos.excluir');
    Route::get('/produtos/{produto}/estoque', [EstoqueController::class, 'exibir'])->name('estoque.exibir');
    Route::post('/produtos/{produto}/estoque', [EstoqueController::class, 'registrar'])->name('estoque.registrar');

    Route::get('/categorias', [AdminCategoriaController::class, 'listar'])->name('categorias.listar');
    Route::get('/categorias/criar', [AdminCategoriaController::class, 'criar'])->name('categorias.criar');
    Route::post('/categorias', [AdminCategoriaController::class, 'salvar'])->name('categorias.salvar');
    Route::get('/categorias/{categoria}/editar', [AdminCategoriaController::class, 'editar'])->name('categorias.editar');
    Route::patch('/categorias/{categoria}', [AdminCategoriaController::class, 'atualizar'])->name('categorias.atualizar');
    Route::delete('/categorias/{categoria}', [AdminCategoriaController::class, 'excluir'])->name('categorias.excluir');

    Route::get('/cupons', [AdminCupomController::class, 'listar'])->name('cupons.listar');
    Route::get('/cupons/criar', [AdminCupomController::class, 'criar'])->name('cupons.criar');
    Route::post('/cupons', [AdminCupomController::class, 'salvar'])->name('cupons.salvar');
    Route::get('/cupons/{cupom}/editar', [AdminCupomController::class, 'editar'])->name('cupons.editar');
    Route::patch('/cupons/{cupom}', [AdminCupomController::class, 'atualizar'])->name('cupons.atualizar');

    Route::get('/frete', [FaixaFreteController::class, 'listar'])->name('frete.listar');
    Route::get('/frete/criar', [FaixaFreteController::class, 'criar'])->name('frete.criar');
    Route::post('/frete', [FaixaFreteController::class, 'salvar'])->name('frete.salvar');
    Route::get('/frete/{faixa}/editar', [FaixaFreteController::class, 'editar'])->name('frete.editar');
    Route::patch('/frete/{faixa}', [FaixaFreteController::class, 'atualizar'])->name('frete.atualizar');
    Route::delete('/frete/{faixa}', [FaixaFreteController::class, 'excluir'])->name('frete.excluir');

    Route::get('/pedidos', [AdminPedidoController::class, 'listar'])->name('pedidos.listar');
    Route::get('/pedidos/{pedido}', [AdminPedidoController::class, 'exibir'])->name('pedidos.exibir');

    Route::get('/usuarios', [AdminUsuarioController::class, 'listar'])->name('usuarios.listar');
    Route::post('/usuarios/{usuario}/promover', [AdminUsuarioController::class, 'promover'])->name('usuarios.promover');
    Route::post('/usuarios/{usuario}/rebaixar', [AdminUsuarioController::class, 'rebaixar'])->name('usuarios.rebaixar');

    Route::redirect('/dashboard', '/admin/painel');
    Route::redirect('/products', '/admin/produtos');
    Route::redirect('/products/create', '/admin/produtos/criar');
});

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'editar'])->name('perfil.editar');
    Route::patch('/perfil', [PerfilController::class, 'atualizar'])->name('perfil.atualizar');
    Route::delete('/perfil', [PerfilController::class, 'excluir'])->name('perfil.excluir');
    Route::redirect('/profile', '/perfil');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/favoritos', [FavoritoController::class, 'listar'])->name('conta.favoritos');
    Route::post('/produtos/{produto}/favoritos', [FavoritoController::class, 'adicionar'])->name('conta.favoritos.adicionar');
    Route::delete('/produtos/{produto}/favoritos', [FavoritoController::class, 'remover'])->name('conta.favoritos.remover');

    Route::get('/enderecos', [EnderecoController::class, 'listar'])->name('conta.enderecos.listar');
    Route::get('/enderecos/criar', [EnderecoController::class, 'criar'])->name('conta.enderecos.criar');
    Route::post('/enderecos', [EnderecoController::class, 'salvar'])->name('conta.enderecos.salvar');
    Route::get('/enderecos/{endereco}/editar', [EnderecoController::class, 'editar'])->name('conta.enderecos.editar');
    Route::patch('/enderecos/{endereco}', [EnderecoController::class, 'atualizar'])->name('conta.enderecos.atualizar');
    Route::delete('/enderecos/{endereco}', [EnderecoController::class, 'excluir'])->name('conta.enderecos.excluir');
    Route::post('/enderecos/{endereco}/padrao', [EnderecoController::class, 'tornarPadrao'])->name('conta.enderecos.padrao');

    Route::get('/pedidos', [ContaPedidoController::class, 'listar'])->name('conta.pedidos.listar');
    Route::get('/pedidos/{pedido}', [ContaPedidoController::class, 'exibir'])->name('conta.pedidos.exibir');

    Route::post('/carrinho/cupom', [CarrinhoController::class, 'aplicarCupom'])->name('loja.carrinho.cupom')->block(10, 10);
    Route::post('/carrinho/endereco', [CarrinhoController::class, 'escolherEndereco'])->name('loja.carrinho.endereco')->block(10, 10);

    Route::get('/checkout', [CheckoutController::class, 'revisar'])->name('checkout.revisar');
    Route::post('/checkout', [CheckoutController::class, 'confirmar'])->name('checkout.confirmar')->block(10, 10);
});

require __DIR__.'/auth.php';
