<?php

namespace App\Http\Controllers\Loja;

use App\Http\Controllers\Controller;
use App\Support\ConsultaCatalogo;
use App\Support\CoresProduto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function __invoke(Request $request, ConsultaCatalogo $consulta): View
    {
        return view('loja.inicio', [
            'produtos' => $consulta->paginar($request),
            'filtros' => ConsultaCatalogo::filtros($request),
            'cores' => CoresProduto::todas(),
            'categoriaAtual' => null,
        ]);
    }
}
