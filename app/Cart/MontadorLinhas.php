<?php

namespace App\Cart;

use App\Models\Produto;
use Illuminate\Support\Collection;

final class MontadorLinhas
{
    /**
     * @param  list<array{id: string, produto_id: int, cor: string, quantidade: int}>  $itens
     * @return Collection<int, LinhaCarrinho>
     */
    public static function deItens(array $itens): Collection
    {
        $ids = array_values(array_unique(array_column($itens, 'produto_id')));
        $produtos = Produto::query()
            ->with('imagens')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $pedidoPorProduto = [];

        foreach ($itens as $item) {
            $pedidoPorProduto[$item['produto_id']] = ($pedidoPorProduto[$item['produto_id']] ?? 0) + $item['quantidade'];
        }

        return collect($itens)->map(function (array $item) use ($produtos, $pedidoPorProduto): LinhaCarrinho {
            $produto = $produtos->get($item['produto_id']);

            return new LinhaCarrinho(
                id: $item['id'],
                idProduto: $item['produto_id'],
                cor: $item['cor'],
                quantidade: $item['quantidade'],
                produto: $produto,
                situacao: self::situacaoDe($produto, $item['cor'], $pedidoPorProduto[$item['produto_id']]),
            );
        })->values();
    }

    public static function situacaoDe(?Produto $produto, string $cor, int $pedidoDoProduto): string
    {
        if ($produto === null || ! $produto->estaPublicado() || $produto->quantidade < 1) {
            return LinhaCarrinho::SITUACAO_INDISPONIVEL;
        }

        if (! in_array($cor, $produto->coresExibidas(), true) || $pedidoDoProduto > $produto->quantidade) {
            return LinhaCarrinho::SITUACAO_AJUSTAR;
        }

        return LinhaCarrinho::SITUACAO_DISPONIVEL;
    }

    public static function estoqueDisponivelParaProduto(Produto $produto, array $itens, ?string $excetoId = null): int
    {
        $usado = 0;

        foreach ($itens as $item) {
            if ($excetoId !== null && $item['id'] === $excetoId) {
                continue;
            }

            if ($item['produto_id'] === $produto->id) {
                $usado += $item['quantidade'];
            }
        }

        return max(0, $produto->quantidade - $usado);
    }
}
