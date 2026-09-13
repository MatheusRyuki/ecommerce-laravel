<?php

namespace App\Http\Controllers\E2e;

use App\Http\Controllers\Controller;
use App\Support\IsolamentoE2e;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DiagnosticoE2eController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! IsolamentoE2e::tokenValido($request->header('X-Token-E2e'))) {
            throw new AccessDeniedHttpException;
        }

        IsolamentoE2e::garantir();

        return response()->json(IsolamentoE2e::diagnostico());
    }
}
