<?php

namespace App\Cart;

use App\Models\EstadoCarrinho;
use App\Models\ItemCarrinho;
use App\Models\Produto;
use App\Models\Usuario;
use App\Support\CoresProduto;
use App\Support\Dinheiro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CarrinhoConta
{
    public function __construct(private Usuario $usuario) {}

    /**
     * @return list<array{id: string, produto_id: int, cor: string, quantidade: int}>
     */
    public function itens(): array
    {
        return $this->itensDoBanco();
    }

    public function quantidadeTotal(): int
    {
        return array_sum(array_column($this->itensDoBanco(), 'quantidade'));
    }

    public function adicionar(int $idProduto, string $cor, int $quantidade): void
    {
        if ($quantidade < 1) {
            throw new ExcecaoNaoPodeAdicionarCarrinho('A quantidade deve ser um inteiro positivo.');
        }

        $cor = CoresProduto::normalizar($cor);

        DB::transaction(function () use ($idProduto, $cor, $quantidade): void {
            $produto = Produto::query()->whereKey($idProduto)->lockForUpdate()->first();

            if ($produto === null || ! $produto->estaDisponivel()) {
                throw new ExcecaoNaoPodeAdicionarCarrinho('Este produto não pode ser adicionado ao carrinho.');
            }

            if (! in_array($cor, $produto->coresExibidas(), true)) {
                throw new ExcecaoNaoPodeAdicionarCarrinho('A cor selecionada não está disponível para este produto.');
            }

            ItemCarrinho::query()->where('usuario_id', $this->usuario->id)->lockForUpdate()->get();

            $itens = $this->itensDoBanco();
            $ja = 0;

            foreach ($itens as $item) {
                if ($item['produto_id'] === $idProduto) {
                    $ja += $item['quantidade'];
                }
            }

            if ($ja + $quantidade > $produto->quantidade) {
                throw new ExcecaoNaoPodeAdicionarCarrinho('A quantidade pedida ultrapassa o estoque disponível.');
            }

            $existente = ItemCarrinho::query()
                ->where('usuario_id', $this->usuario->id)
                ->where('produto_id', $idProduto)
                ->where('cor', $cor)
                ->first();

            if ($existente !== null) {
                $existente->quantidade += $quantidade;
                $existente->save();

                return;
            }

            ItemCarrinho::query()->create([
                'usuario_id' => $this->usuario->id,
                'produto_id' => $idProduto,
                'cor' => $cor,
                'quantidade' => $quantidade,
            ]);
        });
    }

    public function atualizarQuantidade(string $idItem, int $quantidade): void
    {
        if ($quantidade < 1) {
            throw new ExcecaoCarrinho('A quantidade deve ser um inteiro positivo.');
        }

        DB::transaction(function () use ($idItem, $quantidade): void {
            $linha = ItemCarrinho::query()
                ->where('usuario_id', $this->usuario->id)
                ->whereKey($idItem)
                ->lockForUpdate()
                ->first();

            if ($linha === null) {
                throw new ExcecaoItemCarrinhoAusente('Item do carrinho não encontrado.');
            }

            $produto = Produto::query()->whereKey($linha->produto_id)->lockForUpdate()->first();

            if ($produto === null || ! $produto->estaDisponivel() || ! in_array($linha->cor, $produto->coresExibidas(), true)) {
                throw new ExcecaoCarrinho('Este item do carrinho não pode ser atualizado.');
            }

            $outras = (int) ItemCarrinho::query()
                ->where('usuario_id', $this->usuario->id)
                ->where('produto_id', $linha->produto_id)
                ->where('id', '!=', $linha->id)
                ->lockForUpdate()
                ->sum('quantidade');

            if ($quantidade > $linha->quantidade && ($outras + $quantidade) > $produto->quantidade) {
                throw new ExcecaoCarrinho('A quantidade pedida ultrapassa o estoque disponível.');
            }

            $linha->quantidade = $quantidade;
            $linha->save();
        });
    }

    public function remover(string $idItem): void
    {
        $apagados = ItemCarrinho::query()
            ->where('usuario_id', $this->usuario->id)
            ->whereKey($idItem)
            ->delete();

        if ($apagados === 0) {
            throw new ExcecaoItemCarrinhoAusente('Item do carrinho não encontrado.');
        }
    }

    public function limpar(): void
    {
        ItemCarrinho::query()->where('usuario_id', $this->usuario->id)->delete();
    }

    /**
     * @param  list<array{id?: string, produto_id: int, cor: string, quantidade: int}>  $itens
     */
    public function substituir(array $itens): void
    {
        DB::transaction(function () use ($itens): void {
            ItemCarrinho::query()->where('usuario_id', $this->usuario->id)->lockForUpdate()->get();
            ItemCarrinho::query()->where('usuario_id', $this->usuario->id)->delete();

            foreach ($itens as $item) {
                ItemCarrinho::query()->create([
                    'usuario_id' => $this->usuario->id,
                    'produto_id' => $item['produto_id'],
                    'cor' => CoresProduto::normalizar($item['cor']),
                    'quantidade' => $item['quantidade'],
                ]);
            }
        });
    }

    /**
     * @return Collection<int, LinhaCarrinho>
     */
    public function linhas(): Collection
    {
        return MontadorLinhas::deItens($this->itensDoBanco());
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

    public function estado(): EstadoCarrinho
    {
        return EstadoCarrinho::query()->firstOrCreate(
            ['usuario_id' => $this->usuario->id],
            ['cupom_codigo' => null, 'endereco_id' => null],
        );
    }

    /**
     * @return list<array{id: string, produto_id: int, cor: string, quantidade: int}>
     */
    private function itensDoBanco(): array
    {
        return ItemCarrinho::query()
            ->where('usuario_id', $this->usuario->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ItemCarrinho $item): array => [
                'id' => (string) $item->id,
                'produto_id' => $item->produto_id,
                'cor' => CoresProduto::normalizar($item->cor),
                'quantidade' => $item->quantidade,
            ])
            ->all();
    }
}
