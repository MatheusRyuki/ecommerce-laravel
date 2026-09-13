<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisicaoAtualizacaoProduto;
use App\Http\Requests\RequisicaoCadastroProduto;
use App\Models\Categoria;
use App\Models\Produto;
use App\Services\AtualizadorProduto;
use App\Services\CriadorProduto;
use App\Services\DuplicadorProduto;
use App\Services\ExcluirProduto;
use App\Support\CoresProduto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProdutoController extends Controller
{
    public function listar(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $consulta = Produto::query()->with(['imagens', 'categoria'])->orderByDesc('created_at')->orderByDesc('id');

        if ($q !== '') {
            $consulta->where(function ($interno) use ($q): void {
                $interno->where('nome', 'like', '%'.$q.'%')->orWhere('sku', 'like', '%'.$q.'%');
            });
        }

        return view('admin.produtos.listar', [
            'produtos' => $consulta->paginate(15)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function criar(Request $request): View
    {
        $origem = null;

        if ($request->filled('origem')) {
            $origem = Produto::query()->with('imagens')->find($request->query('origem'));
        }

        return view('admin.produtos.criar', $this->dadosFormulario($origem));
    }

    public function salvar(RequisicaoCadastroProduto $request, CriadorProduto $criador, DuplicadorProduto $duplicador): RedirectResponse
    {
        try {
            if ($request->filled('produto_origem_id')) {
                $origem = Produto::query()->findOrFail($request->input('produto_origem_id'));
                $duplicador->duplicar($origem, $request->validated(), $request->user());
            } else {
                $criador->criar($request->validated(), $request->file('imagens', []));
            }
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['sku' => 'Este SKU já está em uso.'])->withInput();
        } catch (RuntimeException $excecao) {
            return back()->withErrors(['imagens' => $excecao->getMessage()])->withInput();
        }

        return redirect()->route('admin.produtos.listar')->with('status', 'produto-criado');
    }

    public function editar(Produto $produto): View
    {
        $produto->load(['imagens', 'categoria']);

        return view('admin.produtos.editar', array_merge($this->dadosFormulario($produto), [
            'produto' => $produto,
        ]));
    }

    public function atualizar(RequisicaoAtualizacaoProduto $request, Produto $produto, AtualizadorProduto $atualizador): RedirectResponse
    {
        $atributos = $request->safe()->except(['imagens', 'ids_imagens_remover']);

        try {
            $atualizador->atualizar(
                $produto,
                $atributos,
                $request->novosArquivosImagem(),
                $request->validated('ids_imagens_remover') ?? [],
            );
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['sku' => 'Este SKU já está em uso.'])->withInput();
        } catch (RuntimeException $excecao) {
            return back()->withErrors(['imagens' => $excecao->getMessage()])->withInput();
        }

        return redirect()->route('admin.produtos.listar')->with('status', 'produto-atualizado');
    }

    public function excluir(Produto $produto, ExcluirProduto $excluir): RedirectResponse
    {
        $pendencias = $excluir->excluir($produto);

        return redirect()
            ->route('admin.produtos.listar')
            ->with('status', $pendencias === [] ? 'produto-excluido' : 'produto-excluido-com-limpeza-pendente');
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosFormulario(?Produto $origem): array
    {
        return [
            'cores' => CoresProduto::todas(),
            'categorias' => Categoria::query()->orderBy('nome')->get(),
            'origem' => $origem,
        ];
    }
}
