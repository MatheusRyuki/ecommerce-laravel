<?php

namespace App\Http\Controllers\Loja;

use App\Cart\CarrinhoConta;
use App\Cart\CarrinhoSessao;
use App\Cart\ExcecaoCarrinho;
use App\Cart\ExcecaoItemCarrinhoAusente;
use App\Cart\ExcecaoNaoPodeAdicionarCarrinho;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequisicaoAdicaoItemCarrinho;
use App\Http\Requests\RequisicaoAtualizacaoItemCarrinho;
use App\Models\Cupom;
use App\Models\Endereco;
use App\Services\CalculadoraCheckout;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CarrinhoController extends Controller
{
    public function __construct(
        private CarrinhoSessao $sessao,
        private CalculadoraCheckout $calculadora,
    ) {}

    public function exibir(Request $request): View
    {
        $carrinho = $this->carrinho($request);
        $linhas = $carrinho->linhas();
        $totalProdutos = $carrinho->totalProdutos($linhas);
        $checkout = null;

        if ($request->user()?->hasVerifiedEmail()) {
            $checkout = $this->calculadora->calcular($request->user(), $linhas, $carrinho);
        }

        $enderecos = $request->user()
            ? Endereco::query()->where('usuario_id', $request->user()->id)->orderByDesc('padrao')->orderBy('id')->get()
            : collect();

        $estado = $request->user() ? $carrinho->estado() : null;

        return view('loja.carrinho', [
            'linhas' => $linhas,
            'totalProdutos' => $totalProdutos,
            'totalProdutosFormatado' => $totalProdutos === null ? null : Dinheiro::formatarBrl($totalProdutos),
            'checkout' => $checkout,
            'enderecos' => $enderecos,
            'cupomCodigo' => $estado?->cupom_codigo,
            'enderecoId' => $estado?->endereco_id,
            'autenticado' => $request->user() !== null,
            'verificado' => $request->user()?->hasVerifiedEmail() === true,
        ]);
    }

    public function adicionarItem(RequisicaoAdicaoItemCarrinho $request): RedirectResponse
    {
        $carrinho = $this->carrinho($request);

        if ($carrinho instanceof CarrinhoSessao) {
            $itensAnteriores = $carrinho->itens();
        }

        try {
            $carrinho->adicionar(
                (int) $request->validated('produto_id'),
                (string) $request->validated('cor'),
                (int) $request->validated('quantidade'),
            );
        } catch (ExcecaoNaoPodeAdicionarCarrinho|ExcecaoCarrinho $excecao) {
            if ($carrinho instanceof CarrinhoSessao) {
                $carrinho->restaurar($itensAnteriores);
            }

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
        $carrinho = $this->carrinho($request);

        if ($carrinho instanceof CarrinhoSessao) {
            $itensAnteriores = $carrinho->itens();
        }

        try {
            $carrinho->atualizarQuantidade($item, (int) $request->validated('quantidade'));
        } catch (ExcecaoItemCarrinhoAusente) {
            throw new NotFoundHttpException;
        } catch (ExcecaoCarrinho $excecao) {
            if ($carrinho instanceof CarrinhoSessao) {
                $carrinho->restaurar($itensAnteriores);
            }

            return back()
                ->withInput()
                ->withErrors(['itens_carrinho.'.$item => $excecao->getMessage()]);
        }

        return redirect()
            ->route('carrinho')
            ->with('status', 'O carrinho foi atualizado.');
    }

    public function removerItem(Request $request, string $item): RedirectResponse
    {
        try {
            $this->carrinho($request)->remover($item);
        } catch (ExcecaoItemCarrinhoAusente) {
            throw new NotFoundHttpException;
        }

        return redirect()
            ->route('carrinho')
            ->with('status', 'O item foi removido do carrinho.');
    }

    public function aplicarCupom(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario?->hasVerifiedEmail(), 403);

        $codigo = Cupom::normalizarCodigo((string) $request->input('cupom'));
        $carrinho = new CarrinhoConta($usuario);
        $estado = $carrinho->estado();

        if ($codigo === '') {
            $estado->cupom_codigo = null;
            $estado->save();

            return redirect()->route('carrinho')->with('status', 'Cupom removido.');
        }

        $cupom = Cupom::query()->where('codigo', $codigo)->first();

        if ($cupom === null || ! $cupom->estaUtilizavel()) {
            return back()->withErrors(['cupom' => 'Este cupom não é válido.']);
        }

        $estado->cupom_codigo = $codigo;
        $estado->save();

        return redirect()->route('carrinho')->with('status', 'Cupom aplicado. Ele só será consumido na confirmação do pedido.');
    }

    public function escolherEndereco(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario?->hasVerifiedEmail(), 403);

        $endereco = Endereco::query()
            ->where('usuario_id', $usuario->id)
            ->whereKey($request->input('endereco_id'))
            ->firstOrFail();

        $estado = (new CarrinhoConta($usuario))->estado();
        $estado->endereco_id = $endereco->id;
        $estado->save();

        return redirect()->route('carrinho')->with('status', 'Endereço de entrega selecionado.');
    }

    private function carrinho(Request $request): CarrinhoSessao|CarrinhoConta
    {
        $usuario = $request->user();

        return $usuario ? new CarrinhoConta($usuario) : $this->sessao;
    }
}
