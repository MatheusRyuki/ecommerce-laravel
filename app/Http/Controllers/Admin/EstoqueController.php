<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Services\RegistradorEstoque;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class EstoqueController extends Controller
{
    public function exibir(Produto $produto): View
    {
        $movimentacoes = MovimentacaoEstoque::query()
            ->where('produto_id', $produto->id)
            ->with('usuario')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.estoque.exibir', [
            'produto' => $produto,
            'movimentacoes' => $movimentacoes,
        ]);
    }

    public function registrar(Request $request, Produto $produto, RegistradorEstoque $estoque): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', Rule::in([
                MovimentacaoEstoque::TIPO_ENTRADA,
                MovimentacaoEstoque::TIPO_SAIDA,
                MovimentacaoEstoque::TIPO_AJUSTE,
            ])],
            'quantidade' => ['required', 'integer', 'min:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        try {
            if ($dados['tipo'] === MovimentacaoEstoque::TIPO_AJUSTE) {
                $estoque->definir(
                    $produto,
                    (int) $dados['quantidade'],
                    MovimentacaoEstoque::TIPO_AJUSTE,
                    $dados['motivo'],
                    $request->user(),
                );
            } elseif ($dados['tipo'] === MovimentacaoEstoque::TIPO_ENTRADA) {
                $estoque->aplicarDelta(
                    $produto,
                    (int) $dados['quantidade'],
                    MovimentacaoEstoque::TIPO_ENTRADA,
                    $dados['motivo'],
                    $request->user(),
                );
            } else {
                $estoque->aplicarDelta(
                    $produto,
                    -1 * (int) $dados['quantidade'],
                    MovimentacaoEstoque::TIPO_SAIDA,
                    $dados['motivo'],
                    $request->user(),
                );
            }
        } catch (InvalidArgumentException $excecao) {
            return back()->withErrors(['quantidade' => $excecao->getMessage()])->withInput();
        }

        return redirect()->route('admin.estoque.exibir', $produto)->with('status', 'Estoque atualizado.');
    }
}
