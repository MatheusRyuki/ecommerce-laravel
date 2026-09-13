<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaixaFrete;
use App\Support\Cep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FaixaFreteController extends Controller
{
    public function listar(): View
    {
        return view('admin.frete.listar', [
            'faixas' => FaixaFrete::query()->orderBy('cep_inicio')->paginate(15),
        ]);
    }

    public function criar(): View
    {
        return view('admin.frete.formulario', ['faixa' => null]);
    }

    public function salvar(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);
        FaixaFrete::query()->create($dados);

        return redirect()->route('admin.frete.listar')->with('status', 'Faixa de frete cadastrada.');
    }

    public function editar(FaixaFrete $faixa): View
    {
        return view('admin.frete.formulario', ['faixa' => $faixa]);
    }

    public function atualizar(Request $request, FaixaFrete $faixa): RedirectResponse
    {
        $faixa->update($this->validar($request, $faixa->id));

        return redirect()->route('admin.frete.listar')->with('status', 'Faixa de frete atualizada.');
    }

    public function excluir(FaixaFrete $faixa): RedirectResponse
    {
        $faixa->delete();

        return redirect()->route('admin.frete.listar')->with('status', 'Faixa de frete excluída.');
    }

    /**
     * @return array{cep_inicio: string, cep_fim: string, valor: mixed}
     */
    private function validar(Request $request, ?int $ignorar = null): array
    {
        $inicio = Cep::normalizar((string) $request->input('cep_inicio'));
        $fim = Cep::normalizar((string) $request->input('cep_fim'));
        $request->merge(['cep_inicio' => $inicio, 'cep_fim' => $fim]);

        $dados = $request->validate([
            'cep_inicio' => ['required', 'digits:8'],
            'cep_fim' => ['required', 'digits:8'],
            'valor' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        if ($dados['cep_inicio'] > $dados['cep_fim']) {
            return tap($dados, fn () => throw ValidationException::withMessages([
                'cep_fim' => 'O CEP final deve ser maior ou igual ao inicial.',
            ]));
        }

        $sobreposta = FaixaFrete::query()->sobrepostas($dados['cep_inicio'], $dados['cep_fim'], $ignorar)->exists();

        if ($sobreposta) {
            throw ValidationException::withMessages([
                'cep_inicio' => 'Esta faixa se sobrepõe a outra já cadastrada.',
            ]);
        }

        return $dados;
    }
}
