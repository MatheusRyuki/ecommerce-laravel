<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequisicaoAtualizacaoPerfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function editar(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function atualizar(RequisicaoAtualizacaoPerfil $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('perfil.editar')->with('status', 'perfil-atualizado');
    }

    public function excluir(Request $request): RedirectResponse
    {
        $request->validateWithBag('exclusao_usuario', [
            'password' => ['required', 'current_password'],
        ]);

        $usuario = $request->user();

        Auth::logout();

        $usuario->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
