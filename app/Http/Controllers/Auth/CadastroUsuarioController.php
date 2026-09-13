<?php

namespace App\Http\Controllers\Auth;

use App\Cart\CarrinhoSessao;
use App\Cart\MescladorCarrinho;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CadastroUsuarioController extends Controller
{
    public function exibir(): View
    {
        return view('autenticacao.cadastrar');
    }

    /**
     * @throws ValidationException
     */
    public function cadastrar(Request $request, CarrinhoSessao $sessao, MescladorCarrinho $mesclador): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Usuario::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $visitante = $sessao->itens();

        $usuario = Usuario::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($usuario));

        Auth::login($usuario);

        $request->session()->regenerate();

        $mesclador->mesclar($usuario, $visitante, $sessao);

        return redirect()->route('verification.notice');
    }
}
