<?php

namespace App\Cart;

use App\Models\Produto;
use App\Models\Usuario;
use App\Support\CoresProduto;
use Illuminate\Support\Facades\DB;

final class MescladorCarrinho
{
    /**
     * @param  list<array{id: string, produto_id: int, cor: string, quantidade: int}>  $visitante
     * @return array{ajustes: list<string>, descartados: list<string>}
     */
    public function mesclar(Usuario $usuario, array $visitante, CarrinhoSessao $sessao): array
    {
        $ajustes = [];
        $descartados = [];

        DB::transaction(function () use ($usuario, $visitante, $sessao, &$ajustes, &$descartados): void {
            $conta = new CarrinhoConta($usuario);
            ItemCarrinhoLock::travar($usuario->id);

            $contaItens = $conta->itens();
            $porChave = [];

            foreach ($contaItens as $item) {
                $porChave[$this->chave($item['produto_id'], $item['cor'])] = $item['quantidade'];
            }

            $porProdutoConta = [];
            foreach ($contaItens as $item) {
                $porProdutoConta[$item['produto_id']] = ($porProdutoConta[$item['produto_id']] ?? 0) + $item['quantidade'];
            }

            $ids = array_values(array_unique(array_merge(
                array_column($contaItens, 'produto_id'),
                array_column($visitante, 'produto_id'),
            )));

            $produtos = Produto::query()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            foreach ($visitante as $item) {
                $produto = $produtos->get($item['produto_id']);
                $cor = CoresProduto::normalizar($item['cor']);
                $chave = $this->chave($item['produto_id'], $cor);

                if ($produto === null || ! $produto->estaPublicado() || $produto->quantidade < 1 || ! in_array($cor, $produto->coresExibidas(), true)) {
                    $descartados[] = $produto?->nome ?? 'Item removido';

                    continue;
                }

                $naContaProduto = $porProdutoConta[$item['produto_id']] ?? 0;
                $restoEstoque = $produto->quantidade - $naContaProduto;

                if ($restoEstoque < 1) {
                    $descartados[] = $produto->nome.' ('.$cor.')';

                    continue;
                }

                $incorporar = min($item['quantidade'], $restoEstoque);
                $porChave[$chave] = ($porChave[$chave] ?? 0) + $incorporar;
                $porProdutoConta[$item['produto_id']] = $naContaProduto + $incorporar;

                if ($incorporar < $item['quantidade']) {
                    $ajustes[] = $produto->nome.' ('.$cor.'): quantidade ajustada para o estoque disponível.';
                }
            }

            $final = [];

            foreach ($porChave as $chave => $quantidade) {
                if ($quantidade < 1) {
                    continue;
                }

                [$produtoId, $cor] = explode(':', $chave, 2);
                $final[] = [
                    'produto_id' => (int) $produtoId,
                    'cor' => $cor,
                    'quantidade' => $quantidade,
                ];
            }

            $conta->substituir($final);
            $sessao->limpar();
        });

        return ['ajustes' => $ajustes, 'descartados' => $descartados];
    }

    private function chave(int $produtoId, string $cor): string
    {
        return $produtoId.':'.$cor;
    }
}
