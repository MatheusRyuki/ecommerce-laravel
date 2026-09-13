<?php

namespace App\Http\Controllers\Conta;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function listar(Request $request): View
    {
        $pedidos = Pedido::query()
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(10);

        return view('conta.pedidos.listar', ['pedidos' => $pedidos]);
    }

    public function exibir(Request $request, Pedido $pedido): View
    {
        abort_unless($pedido->usuario_id === $request->user()->id, 404);

        return view('conta.pedidos.exibir', ['pedido' => $pedido->load('itens')]);
    }
}
