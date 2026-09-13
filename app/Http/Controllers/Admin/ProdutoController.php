<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisicaoAtualizacaoProduto;
use App\Http\Requests\RequisicaoCadastroProduto;
use App\Models\Produto;
use App\Services\AtualizadorProduto;
use App\Services\CriadorProduto;
use App\Services\ExcluirProduto;
use App\Support\CoresProduto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class ProdutoController extends Controller
{
    public function index(): View
    {
        $produtos = Produto::query()
            ->with('imagens')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.produtos.index', [
            'produtos' => $produtos,
        ]);
    }

    public function criar(): View
    {
        return view('admin.produtos.criar', [
            'cores' => CoresProduto::todas(),
        ]);
    }

    public function salvar(RequisicaoCadastroProduto $request, CriadorProduto $criador): RedirectResponse
    {
        try {
            $criador->criar($request->validated(), $request->file('imagens', []));
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['sku' => 'Este SKU já está em uso.'])
                ->withInput();
        } catch (RuntimeException $excecao) {
            return back()
                ->withErrors(['imagens' => $excecao->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.produtos.index')
            ->with('status', 'produto-criado');
    }

    public function editar(Produto $produto): View
    {
        $produto->load('imagens');

        return view('admin.produtos.editar', [
            'produto' => $produto,
            'cores' => CoresProduto::todas(),
        ]);
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
            return back()
                ->withErrors(['sku' => 'Este SKU já está em uso.'])
                ->withInput();
        } catch (RuntimeException $excecao) {
            return back()
                ->withErrors(['imagens' => $excecao->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.produtos.index')
            ->with('status', 'produto-atualizado');
    }

    public function excluir(Produto $produto, ExcluirProduto $excluir): RedirectResponse
    {
        $pendencias = $excluir->excluir($produto);

        return redirect()
            ->route('admin.produtos.index')
            ->with('status', $pendencias === [] ? 'produto-excluido' : 'produto-excluido-com-limpeza-pendente');
    }
}
