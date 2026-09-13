<?php

namespace App\Http\Controllers\Loja;

use App\Cart\CarrinhoConta;
use App\Http\Controllers\Controller;
use App\Services\CalculadoraCheckout;
use App\Services\ConfirmadorPedido;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    public function revisar(Request $request, CalculadoraCheckout $calculadora): View|RedirectResponse
    {
        $usuario = $request->user();
        $carrinho = new CarrinhoConta($usuario);
        $linhas = $carrinho->linhas();
        $calculo = $calculadora->calcular($usuario, $linhas, $carrinho);

        if (! $calculo['valido']) {
            return redirect()->route('carrinho')->withErrors(['checkout' => $calculo['motivo']]);
        }

        $estado = $carrinho->estado();
        $chave = $estado->chave_checkout ?: (string) Str::uuid();
        $estado->chave_checkout = $chave;
        $estado->revisao_checkout = ['assinatura' => $calculo['assinatura']];
        $estado->save();

        return view('loja.checkout', [
            'linhas' => $linhas,
            'calculo' => $calculo,
            'chave' => $chave,
            'subtotalFormatado' => Dinheiro::formatarBrl($calculo['subtotal']),
            'descontoFormatado' => Dinheiro::formatarBrl($calculo['desconto']),
            'freteFormatado' => Dinheiro::formatarBrl($calculo['frete']),
            'totalFormatado' => Dinheiro::formatarBrl($calculo['total']),
        ]);
    }

    public function confirmar(Request $request, ConfirmadorPedido $confirmador, CalculadoraCheckout $calculadora): RedirectResponse
    {
        $request->validate([
            'chave' => ['required', 'uuid'],
        ]);

        $usuario = $request->user();
        $carrinho = new CarrinhoConta($usuario);
        $estado = $carrinho->estado();

        if ($estado->chave_checkout !== $request->input('chave')) {
            return redirect()->route('checkout.revisar')->withErrors(['checkout' => 'A revisão do pedido expirou. Confira os valores novamente.']);
        }

        $calculo = $calculadora->calcular($usuario, $carrinho->linhas(), $carrinho);

        if (! $calculo['valido']) {
            return redirect()->route('carrinho')->withErrors(['checkout' => $calculo['motivo']]);
        }

        try {
            $pedido = $confirmador->confirmar($usuario, $estado->chave_checkout, $calculo['assinatura']);
        } catch (RuntimeException $excecao) {
            return redirect()->route('checkout.revisar')->withErrors(['checkout' => $excecao->getMessage()]);
        }

        return redirect()
            ->route('conta.pedidos.exibir', $pedido)
            ->with('status', 'Pedido '.$pedido->codigo.' registrado. Nenhum pagamento foi processado.');
    }
}
