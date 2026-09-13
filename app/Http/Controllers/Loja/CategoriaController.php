<?php

namespace App\Http\Controllers\Loja;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Support\ConsultaCatalogo;
use App\Support\CoresProduto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function exibir(Request $request, Categoria $categoria, ConsultaCatalogo $consulta): View
    {
        return view('loja.inicio', [
            'produtos' => $consulta->paginar($request, $categoria),
            'filtros' => ConsultaCatalogo::filtros($request),
            'cores' => CoresProduto::todas(),
            'categoriaAtual' => $categoria,
        ]);
    }
}
