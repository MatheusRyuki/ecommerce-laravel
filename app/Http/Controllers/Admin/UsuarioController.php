<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function listar(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $consulta = Usuario::query()->orderBy('name');

        if ($q !== '') {
            $consulta->where(function ($interno) use ($q): void {
                $interno->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%');
            });
        }

        return view('admin.usuarios.listar', [
            'usuarios' => $consulta->paginate(15)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function promover(Request $request, Usuario $usuario): RedirectResponse
    {
        abort_if($usuario->id === $request->user()->id, 403);

        $usuario->administrador = true;
        $usuario->save();

        return back()->with('status', 'Usuário promovido a administrador.');
    }

    public function rebaixar(Request $request, Usuario $usuario): RedirectResponse
    {
        abort_if($usuario->id === $request->user()->id, 403, 'Você não pode remover o próprio privilégio de administrador.');

        DB::transaction(function () use ($usuario): void {
            $admins = Usuario::query()->where('administrador', true)->lockForUpdate()->get();

            if ($admins->count() <= 1 && $usuario->administrador) {
                abort(422, 'É necessário manter pelo menos um administrador.');
            }

            $alvo = Usuario::query()->whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            $alvo->administrador = false;
            $alvo->save();
        });

        return back()->with('status', 'Privilégio de administrador removido.');
    }
}
