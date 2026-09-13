<?php

namespace App\Http\Controllers\Loja;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function exibir(Produto $produto): View
    {
        $produto->load('imagens');

        return view('loja.detalhes-produto', [
            'produto' => $produto,
        ]);
    }
}
