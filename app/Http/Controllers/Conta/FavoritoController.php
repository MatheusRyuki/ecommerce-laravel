<?php

namespace App\Http\Controllers\Conta;

use App\Http\Controllers\Controller;
use App\Models\Favorito;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoritoController extends Controller
{
    public function listar(Request $request): View
    {
        $favoritos = Favorito::query()
            ->where('usuario_id', $request->user()->id)
            ->whereHas('produto', fn ($consulta) => $consulta->publicados())
            ->with(['produto.imagens'])
            ->orderByDesc('id')
            ->get();

        return view('conta.favoritos', ['favoritos' => $favoritos]);
    }

    public function adicionar(Request $request, Produto $produto): RedirectResponse
    {
        abort_unless($produto->estaPublicado(), 404);

        Favorito::query()->firstOrCreate([
            'usuario_id' => $request->user()->id,
            'produto_id' => $produto->id,
        ]);

        return back()->with('status', 'Produto salvo nos favoritos.');
    }

    public function remover(Request $request, Produto $produto): RedirectResponse
    {
        Favorito::query()
            ->where('usuario_id', $request->user()->id)
            ->where('produto_id', $produto->id)
            ->delete();

        return back()->with('status', 'Produto removido dos favoritos.');
    }
}
