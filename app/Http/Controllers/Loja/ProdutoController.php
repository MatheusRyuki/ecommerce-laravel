<?php

namespace App\Http\Controllers\Loja;

use App\Http\Controllers\Controller;
use App\Models\Favorito;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProdutoController extends Controller
{
    public function exibir(Request $request, Produto $produto): View
    {
        if (! $produto->estaPublicado()) {
            throw new NotFoundHttpException;
        }

        $produto->load('imagens');

        $favorito = false;

        if ($request->user()) {
            $favorito = Favorito::query()
                ->where('usuario_id', $request->user()->id)
                ->where('produto_id', $produto->id)
                ->exists();
        }

        return view('loja.detalhes-produto', [
            'produto' => $produto,
            'favorito' => $favorito,
        ]);
    }
}
