<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function listar(): View
    {
        return view('admin.pedidos.listar', [
            'pedidos' => Pedido::query()->with('usuario')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function exibir(Pedido $pedido): View
    {
        return view('admin.pedidos.exibir', ['pedido' => $pedido->load(['itens', 'usuario'])]);
    }
}
