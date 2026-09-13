<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DestinoAposAutenticacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificacaoVerificacaoEmailController extends Controller
{
    public function reenviar(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(DestinoAposAutenticacao::url());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'link-verificacao-enviado');
    }
}
