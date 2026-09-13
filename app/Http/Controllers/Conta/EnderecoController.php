<?php

namespace App\Http\Controllers\Conta;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisicaoEndereco;
use App\Models\Endereco;
use App\Services\GestorEnderecos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnderecoController extends Controller
{
    public function listar(Request $request): View
    {
        $enderecos = Endereco::query()
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('padrao')
            ->orderBy('id')
            ->get();

        return view('conta.enderecos.listar', ['enderecos' => $enderecos]);
    }

    public function criar(): View
    {
        return view('conta.enderecos.formulario', ['endereco' => null]);
    }

    public function salvar(RequisicaoEndereco $request, GestorEnderecos $gestor): RedirectResponse
    {
        $gestor->criar($request->user(), $request->validated());

        return redirect()->route('conta.enderecos.listar')->with('status', 'Endereço cadastrado.');
    }

    public function editar(Request $request, Endereco $endereco): View
    {
        $this->autorizar($request, $endereco);

        return view('conta.enderecos.formulario', ['endereco' => $endereco]);
    }

    public function atualizar(RequisicaoEndereco $request, Endereco $endereco, GestorEnderecos $gestor): RedirectResponse
    {
        $this->autorizar($request, $endereco);
        $gestor->atualizar($endereco, $request->validated());

        return redirect()->route('conta.enderecos.listar')->with('status', 'Endereço atualizado.');
    }

    public function excluir(Request $request, Endereco $endereco, GestorEnderecos $gestor): RedirectResponse
    {
        $this->autorizar($request, $endereco);
        $gestor->excluir($endereco);

        return redirect()->route('conta.enderecos.listar')->with('status', 'Endereço excluído.');
    }

    public function tornarPadrao(Request $request, Endereco $endereco, GestorEnderecos $gestor): RedirectResponse
    {
        $this->autorizar($request, $endereco);
        $gestor->marcarPadrao($request->user(), $endereco);

        return redirect()->route('conta.enderecos.listar')->with('status', 'Endereço padrão atualizado.');
    }

    private function autorizar(Request $request, Endereco $endereco): void
    {
        abort_unless($endereco->usuario_id === $request->user()->id, 404);
    }
}
