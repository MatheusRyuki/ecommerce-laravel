<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function listar(): View
    {
        return view('admin.categorias.listar', [
            'categorias' => Categoria::query()->orderBy('nome')->paginate(15),
        ]);
    }

    public function criar(): View
    {
        return view('admin.categorias.formulario', ['categoria' => null]);
    }

    public function salvar(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);
        Categoria::query()->create($dados);

        return redirect()->route('admin.categorias.listar')->with('status', 'Categoria cadastrada.');
    }

    public function editar(Categoria $categoria): View
    {
        return view('admin.categorias.formulario', ['categoria' => $categoria]);
    }

    public function atualizar(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->validar($request, $categoria->id));

        return redirect()->route('admin.categorias.listar')->with('status', 'Categoria atualizada.');
    }

    public function excluir(Categoria $categoria): RedirectResponse
    {
        $categoria->delete();

        return redirect()->route('admin.categorias.listar')->with('status', 'Categoria excluída. Os produtos foram preservados.');
    }

    /**
     * @return array{nome: string, slug: string}
     */
    private function validar(Request $request, ?int $ignorar = null): array
    {
        $slug = Str::slug((string) $request->input('slug', $request->input('nome')));

        $request->merge(['slug' => $slug]);

        return $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique('categorias', 'slug')->ignore($ignorar)],
        ]);
    }
}
