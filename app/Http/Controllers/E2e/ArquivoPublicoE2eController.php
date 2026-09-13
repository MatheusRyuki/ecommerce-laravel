<?php

namespace App\Http\Controllers\E2e;

use App\Http\Controllers\Controller;
use App\Support\DiscoArquivosProduto;
use App\Support\IsolamentoE2e;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArquivoPublicoE2eController extends Controller
{
    public function __invoke(Request $request, string $caminho): BinaryFileResponse
    {
        IsolamentoE2e::garantir();

        $relativo = str_replace('\\', '/', $caminho);

        if ($relativo === '' || str_contains($relativo, '..')) {
            throw new NotFoundHttpException;
        }

        $disco = DiscoArquivosProduto::disco();

        if (! $disco->exists($relativo)) {
            throw new NotFoundHttpException;
        }

        $absoluto = $disco->path($relativo);
        $raiz = realpath($disco->path('')) ?: $disco->path('');
        $real = realpath($absoluto);

        if ($real === false || ! str_starts_with($real, $raiz)) {
            throw new NotFoundHttpException;
        }

        return response()->file($real);
    }
}
