<?php

namespace App\Cart;

use App\Models\Produto;
use App\Support\Dinheiro;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CarrinhoSessao
{
    public const CHAVE_SESSAO = 'cart.items';

    public function __construct(private Session $sessao) {}

    /**
     * @return list<array{id: string, product_id: int, color: string, quantity: int}>
     */
    public function itens(): array
    {
        return $this->itensNormalizados();
    }

    public function quantidadeTotal(): int
    {
        return array_sum(array_column($this->itensNormalizados(), 'quantity'));
    }

    public function adicionar(int $idProduto, string $cor, int $quantidade): void
    {
        if ($quantidade < 1) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('A quantidade deve ser um inteiro positivo.');
        }

        $produto = Produto::query()->find($idProduto);

        if ($produto === null || ! $produto->estaDisponivel()) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('Este produto não pode ser adicionado ao carrinho.');
        }

        if (! in_array($cor, $produto->coresExibidas(), true)) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('A cor selecionada não está disponível para este produto.');
        }

        $itens = $this->itensNormalizados();
        $quantidadeJaNoCarrinho = 0;

        foreach ($itens as $item) {
            if ($item['product_id'] === $idProduto) {
                $quantidadeJaNoCarrinho += $item['quantity'];
            }
        }

        if ($quantidadeJaNoCarrinho + $quantidade > $produto->qty) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('A quantidade pedida ultrapassa o estoque disponível.');
        }

        $juntou = false;

        foreach ($itens as $indice => $item) {
            if ($item['product_id'] === $idProduto && $item['color'] === $cor) {
                $itens[$indice]['quantity'] = $item['quantity'] + $quantidade;
                $juntou = true;
                break;
            }
        }

        if (! $juntou) {
            $itens[] = [
                'id' => (string) Str::uuid(),
                'product_id' => $idProduto,
                'color' => $cor,
                'quantity' => $quantidade,
            ];
        }

        $this->sessao->put(self::CHAVE_SESSAO, $itens);
    }

    public function atualizarQuantidade(string $idItem, int $quantidade): void
    {
        if ($quantidade < 1) {
            throw new ExcecaoCarrinho('A quantidade deve ser um inteiro positivo.');
        }

        $itens = $this->itensNormalizados();
        $indice = $this->indiceDe($itens, $idItem);
        $linha = $itens[$indice];
        $produto = Produto::query()->find($linha['product_id']);

        if ($produto === null || ! $produto->estaDisponivel() || ! in_array($linha['color'], $produto->coresExibidas(), true)) {
            throw new ExcecaoCarrinho('Este item do carrinho não pode ser atualizado.');
        }

        $outras = 0;

        foreach ($itens as $indiceItem => $item) {
            if ($indiceItem !== $indice && $item['product_id'] === $linha['product_id']) {
                $outras += $item['quantity'];
            }
        }

        if ($quantidade > $linha['quantity'] && ($outras + $quantidade) > $produto->qty) {
            throw new ExcecaoCarrinho('A quantidade pedida ultrapassa o estoque disponível.');
        }

        $itens[$indice]['quantity'] = $quantidade;
        $this->sessao->put(self::CHAVE_SESSAO, $itens);
    }

    public function remover(string $idItem): void
    {
        $itens = $this->itensNormalizados();
        $indice = $this->indiceDe($itens, $idItem);
        unset($itens[$indice]);
        $this->sessao->put(self::CHAVE_SESSAO, array_values($itens));
    }

    /**
     * @param  Collection<int, LinhaCarrinho>|null  $linhas
     */
    public function totalProdutos(?Collection $linhas = null): ?string
    {
        $linhas ??= $this->linhas();

        if ($linhas->isEmpty()) {
            return '0.00';
        }

        if ($linhas->contains(fn (LinhaCarrinho $linha): bool => ! $linha->entraNoTotal())) {
            return null;
        }

        $subtotais = $linhas
            ->map(fn (LinhaCarrinho $linha): string => $linha->subtotal() ?? '0.00')
            ->all();

        return Dinheiro::somar(...$subtotais);
    }

    /**
     * @param  list<array{id: string, product_id: int, color: string, quantity: int}>  $itens
     */
    private function indiceDe(array $itens, string $idItem): int
    {
        foreach ($itens as $indice => $item) {
            if ($item['id'] === $idItem) {
                return $indice;
            }
        }

        throw new ExcecaoItemCarrinhoAusente('Item do carrinho não encontrado.');
    }

    /**
     * @return Collection<int, LinhaCarrinho>
     */
    public function linhas(): Collection
    {
        $itens = $this->itensNormalizados();
        $ids = array_values(array_unique(array_column($itens, 'product_id')));
        $produtos = Produto::query()
            ->with('imagens')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $pedidoPorProduto = [];

        foreach ($itens as $item) {
            $pedidoPorProduto[$item['product_id']] = ($pedidoPorProduto[$item['product_id']] ?? 0) + $item['quantity'];
        }

        return collect($itens)->map(function (array $item) use ($produtos, $pedidoPorProduto): LinhaCarrinho {
            $produto = $produtos->get($item['product_id']);

            return new LinhaCarrinho(
                id: $item['id'],
                idProduto: $item['product_id'],
                cor: $item['color'],
                quantidade: $item['quantity'],
                produto: $produto,
                situacao: $this->situacaoDe($produto, $item['color'], $pedidoPorProduto[$item['product_id']]),
            );
        })->values();
    }

    /**
     * @return list<array{id: string, product_id: int, color: string, quantity: int}>
     */
    private function itensNormalizados(): array
    {
        $itens = $this->sessao->get(self::CHAVE_SESSAO, []);

        if (! is_array($itens)) {
            return [];
        }

        $normalizados = [];

        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? null;
            $idProduto = $item['product_id'] ?? null;
            $cor = $item['color'] ?? null;
            $quantidade = $item['quantity'] ?? null;

            if (! is_string($id) || $id === '' || ! is_numeric($idProduto) || ! is_string($cor) || $cor === '' || ! is_numeric($quantidade)) {
                continue;
            }

            $quantidade = (int) $quantidade;

            if ($quantidade < 1) {
                continue;
            }

            $normalizados[] = [
                'id' => $id,
                'product_id' => (int) $idProduto,
                'color' => $cor,
                'quantity' => $quantidade,
            ];
        }

        return $normalizados;
    }

    private function situacaoDe(?Produto $produto, string $cor, int $pedidoDoProduto): string
    {
        if ($produto === null || ! $produto->estaDisponivel()) {
            return LinhaCarrinho::SITUACAO_INDISPONIVEL;
        }

        if (! in_array($cor, $produto->coresExibidas(), true) || $pedidoDoProduto > $produto->qty) {
            return LinhaCarrinho::SITUACAO_AJUSTAR;
        }

        return LinhaCarrinho::SITUACAO_DISPONIVEL;
    }
}
