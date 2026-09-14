<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequisicaoAtualizacaoPerfil;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function editar(Request $request): View
    {
        return view('perfil.editar', [
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

        if ($request->user()->wasChanged('email')) {
            $request->user()->sendEmailVerificationNotification();
        }

        return Redirect::route('perfil.editar')->with('status', 'perfil-atualizado');
    }

    public function excluir(Request $request): RedirectResponse
    {
        $request->validateWithBag('exclusao_usuario', [
            'password' => ['required', 'current_password'],
        ]);

        $id = $request->user()->id;
        $bloqueado = false;

        DB::transaction(function () use ($id, &$bloqueado): void {
            $usuario = Usuario::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            if ($usuario->administrador) {
                $admins = Usuario::query()
                    ->where('administrador', true)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($admins->where('id', '!=', $usuario->id)->count() === 0) {
                    $bloqueado = true;

                    return;
                }
            }

            $usuario->delete();
        });

        if ($bloqueado) {
            return back()->withErrors(
                ['exclusao_usuario' => 'Não é possível excluir o último administrador.'],
                'exclusao_usuario',
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
