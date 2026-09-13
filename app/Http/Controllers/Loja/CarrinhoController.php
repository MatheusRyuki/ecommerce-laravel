<?php

namespace App\Http\Controllers\Loja;

use App\Cart\CarrinhoSessao;
use App\Cart\ExcecaoCarrinho;
use App\Cart\ExcecaoItemCarrinhoAusente;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequisicaoAdicaoItemCarrinho;
use App\Http\Requests\RequisicaoAtualizacaoItemCarrinho;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CarrinhoController extends Controller
{
    public function __construct(private CarrinhoSessao $carrinho) {}

    public function index(): View
    {
        $linhas = $this->carrinho->linhas();
        $totalProdutos = $this->carrinho->totalProdutos($linhas);

        return view('loja.carrinho', [
            'linhas' => $linhas,
            'totalProdutos' => $totalProdutos,
            'totalProdutosFormatado' => $totalProdutos === null ? null : Dinheiro::formatarBrl($totalProdutos),
        ]);
    }

    public function adicionarItem(RequisicaoAdicaoItemCarrinho $request): RedirectResponse
    {
        $itensAnteriores = $this->carrinho->itens();

        try {
            $this->carrinho->adicionar(
                (int) $request->validated('produto_id'),
                (string) $request->validated('cor'),
                (int) $request->validated('quantidade'),
            );
        } catch (ExcecaoCarrinho $excecao) {
            $this->carrinho->restaurar($itensAnteriores);

            return back()
                ->withInput()
                ->withErrors(['carrinho' => $excecao->getMessage()]);
        }

        return redirect()
            ->route('carrinho')
            ->with('status', 'O produto foi adicionado ao carrinho.');
    }

    public function atualizarItem(RequisicaoAtualizacaoItemCarrinho $request, string $item): RedirectResponse
    {
        $itensAnteriores = $this->carrinho->itens();

        try {
            $this->carrinho->atualizarQuantidade($item, (int) $request->validated('quantidade'));
        } catch (ExcecaoItemCarrinhoAusente) {
            throw new NotFoundHttpException;
        } catch (ExcecaoCarrinho $excecao) {
            $this->carrinho->restaurar($itensAnteriores);

            return back()
                ->withInput()
                ->withErrors(['itens_carrinho.'.$item => $excecao->getMessage()]);
        }

        return redirect()
            ->route('carrinho')
            ->with('status', 'O carrinho foi atualizado.');
    }

    public function removerItem(string $item): RedirectResponse
    {
        try {
            $this->carrinho->remover($item);
        } catch (ExcecaoItemCarrinhoAusente) {
            throw new NotFoundHttpException;
        }

        return redirect()
            ->route('carrinho')
            ->with('status', 'O item foi removido do carrinho.');
    }
}
