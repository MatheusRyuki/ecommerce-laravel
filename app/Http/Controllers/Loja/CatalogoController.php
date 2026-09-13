<?php

namespace App\Http\Controllers\Loja;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function __invoke(): View
    {
        $produtos = Produto::query()
            ->with('imagens')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('loja.inicio', [
            'produtos' => $produtos,
        ]);
    }
}
