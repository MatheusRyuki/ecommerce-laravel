<?php

namespace App\Cart;

use App\Models\Produto;
use App\Support\CoresProduto;
use App\Support\Dinheiro;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CarrinhoSessao
{
    public const CHAVE_SESSAO = 'carrinho.itens';

    public const CHAVE_SESSAO_LEGADA = 'cart.items';

    public function __construct(private Session $sessao) {}

    /**
     * @return list<array{id: string, produto_id: int, cor: string, quantidade: int}>
     */
    public function itens(): array
    {
        return $this->itensNormalizados();
    }

    public function quantidadeTotal(): int
    {
        return array_sum(array_column($this->itensNormalizados(), 'quantidade'));
    }

    public function adicionar(int $idProduto, string $cor, int $quantidade): void
    {
        if ($quantidade < 1) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('A quantidade deve ser um inteiro positivo.');
        }

        $cor = CoresProduto::normalizar($cor);
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
            if ($item['produto_id'] === $idProduto) {
                $quantidadeJaNoCarrinho += $item['quantidade'];
            }
        }

        if ($quantidadeJaNoCarrinho + $quantidade > $produto->quantidade) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('A quantidade pedida ultrapassa o estoque disponível.');
        }

        $juntou = false;

        foreach ($itens as $indice => $item) {
            if ($item['produto_id'] === $idProduto && $item['cor'] === $cor) {
                $itens[$indice]['quantidade'] = $item['quantidade'] + $quantidade;
                $juntou = true;
                break;
            }
        }

        if (! $juntou) {
            $itens[] = [
                'id' => (string) Str::uuid(),
                'produto_id' => $idProduto,
                'cor' => $cor,
                'quantidade' => $quantidade,
            ];
        }

        $this->gravar($itens);
    }

    public function atualizarQuantidade(string $idItem, int $quantidade): void
    {
        if ($quantidade < 1) {
            throw new ExcecaoCarrinho('A quantidade deve ser um inteiro positivo.');
        }

        $itens = $this->itensNormalizados();
        $indice = $this->indiceDe($itens, $idItem);
        $linha = $itens[$indice];
        $produto = Produto::query()->find($linha['produto_id']);

        if ($produto === null || ! $produto->estaDisponivel() || ! in_array($linha['cor'], $produto->coresExibidas(), true)) {
            throw new ExcecaoCarrinho('Este item do carrinho não pode ser atualizado.');
        }

        $outras = 0;

        foreach ($itens as $indiceItem => $item) {
            if ($indiceItem !== $indice && $item['produto_id'] === $linha['produto_id']) {
                $outras += $item['quantidade'];
            }
        }

        if ($quantidade > $linha['quantidade'] && ($outras + $quantidade) > $produto->quantidade) {
            throw new ExcecaoCarrinho('A quantidade pedida ultrapassa o estoque disponível.');
        }

        $itens[$indice]['quantidade'] = $quantidade;
        $this->gravar($itens);
    }

    public function remover(string $idItem): void
    {
        $itens = $this->itensNormalizados();
        $indice = $this->indiceDe($itens, $idItem);
        unset($itens[$indice]);
        $this->gravar(array_values($itens));
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
     * @param  list<array{id: string, produto_id: int, cor: string, quantidade: int}>  $itens
     */
    public function restaurar(array $itens): void
    {
        $this->gravar($itens);
    }

    /**
     * @param  list<array{id: string, produto_id: int, cor: string, quantidade: int}>  $itens
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
        return MontadorLinhas::deItens($this->itensNormalizados());
    }

    public function limpar(): void
    {
        $this->gravar([]);
    }

    /**
     * @return list<array{id: string, produto_id: int, cor: string, quantidade: int}>
     */
    private function itensNormalizados(): array
    {
        $brutos = $this->sessao->get(self::CHAVE_SESSAO);

        if (! is_array($brutos) || $brutos === []) {
            $legado = $this->sessao->get(self::CHAVE_SESSAO_LEGADA, []);
            $brutos = is_array($legado) ? $legado : [];
        }

        $normalizados = [];

        foreach ($brutos as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? null;
            $idProduto = $item['produto_id'] ?? $item['product_id'] ?? null;
            $cor = $item['cor'] ?? $item['color'] ?? null;
            $quantidade = $item['quantidade'] ?? $item['quantity'] ?? null;

            if (! is_string($id) || $id === '' || ! is_numeric($idProduto) || ! is_string($cor) || $cor === '' || ! is_numeric($quantidade)) {
                continue;
            }

            $quantidade = (int) $quantidade;

            if ($quantidade < 1) {
                continue;
            }

            $normalizados[] = [
                'id' => $id,
                'produto_id' => (int) $idProduto,
                'cor' => CoresProduto::normalizar($cor),
                'quantidade' => $quantidade,
            ];
        }

        return $normalizados;
    }

    /**
     * @param  list<array{id: string, produto_id: int, cor: string, quantidade: int}>  $itens
     */
    private function gravar(array $itens): void
    {
        $this->sessao->put(self::CHAVE_SESSAO, $itens);
        $this->sessao->forget(self::CHAVE_SESSAO_LEGADA);
    }
}
