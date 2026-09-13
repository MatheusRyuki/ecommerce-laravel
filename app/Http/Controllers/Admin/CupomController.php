<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CupomController extends Controller
{
    public function listar(): View
    {
        return view('admin.cupons.listar', [
            'cupons' => Cupom::query()->orderByDesc('id')->paginate(15),
        ]);
    }

    public function criar(): View
    {
        return view('admin.cupons.formulario', ['cupom' => null]);
    }

    public function salvar(Request $request): RedirectResponse
    {
        Cupom::query()->create($this->validar($request));

        return redirect()->route('admin.cupons.listar')->with('status', 'Cupom cadastrado.');
    }

    public function editar(Cupom $cupom): View
    {
        return view('admin.cupons.formulario', ['cupom' => $cupom]);
    }

    public function atualizar(Request $request, Cupom $cupom): RedirectResponse
    {
        $cupom->update($this->validar($request, $cupom->id));

        return redirect()->route('admin.cupons.listar')->with('status', 'Cupom atualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?int $ignorar = null): array
    {
        $request->merge([
            'codigo' => Cupom::normalizarCodigo((string) $request->input('codigo')),
            'ativo' => $request->boolean('ativo'),
            'uso_unico' => $request->boolean('uso_unico'),
        ]);

        return $request->validate([
            'codigo' => ['required', 'string', 'max:40', Rule::unique('cupons', 'codigo')->ignore($ignorar)],
            'tipo' => ['required', Rule::in([Cupom::TIPO_FIXO, Cupom::TIPO_PERCENTUAL])],
            'valor' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'valido_de' => ['nullable', 'date'],
            'valido_ate' => ['nullable', 'date', 'after_or_equal:valido_de'],
            'ativo' => ['boolean'],
            'uso_unico' => ['boolean'],
        ]);
    }
}
