<?php

namespace App\Http\Controllers\Auth;

use App\Cart\CarrinhoSessao;
use App\Cart\MescladorCarrinho;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\DestinoAposAutenticacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SessaoAutenticadaController extends Controller
{
    public function exibir(): View
    {
        return view('autenticacao.entrar');
    }

    public function entrar(LoginRequest $request, CarrinhoSessao $sessao, MescladorCarrinho $mesclador): RedirectResponse
    {
        $visitante = $sessao->itens();

        $request->autenticar();

        $request->session()->regenerate();

        $resultado = $mesclador->mesclar($request->user(), $visitante, $sessao);
        $mensagem = $this->mensagemMescla($resultado);
        $destino = redirect()->intended(DestinoAposAutenticacao::url());

        return $mensagem ? $destino->with('status', $mensagem) : $destino;
    }

    public function sair(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * @param  array{ajustes: list<string>, descartados: list<string>}  $resultado
     */
    private function mensagemMescla(array $resultado): ?string
    {
        $partes = [];

        if ($resultado['ajustes'] !== []) {
            $partes[] = 'Alguns itens do carrinho visitante foram ajustados ao estoque.';
        }

        if ($resultado['descartados'] !== []) {
            $partes[] = 'Itens indisponíveis do carrinho visitante não foram incorporados: '.implode(', ', array_unique($resultado['descartados'])).'.';
        }

        return $partes === [] ? null : implode(' ', $partes);
    }
}
