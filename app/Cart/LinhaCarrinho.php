<?php

namespace App\Cart;

use App\Models\Produto;
use App\Support\CoresProduto;
use App\Support\Dinheiro;

final readonly class LinhaCarrinho
{
    public const SITUACAO_DISPONIVEL = 'disponivel';

    public const SITUACAO_INDISPONIVEL = 'indisponivel';

    public const SITUACAO_AJUSTAR = 'ajustar';

    public function __construct(
        public string $id,
        public int $idProduto,
        public string $cor,
        public int $quantidade,
        public ?Produto $produto,
        public string $situacao,
    ) {}

    public function nome(): string
    {
        return $this->produto?->nome ?? 'Item indisponível';
    }

    public function precoUnitario(): ?string
    {
        if ($this->produto === null) {
            return null;
        }

        return (string) $this->produto->preco;
    }

    public function precoUnitarioFormatado(): ?string
    {
        $preco = $this->precoUnitario();

        return $preco === null ? null : Dinheiro::formatarBrl($preco);
    }

    public function subtotal(): ?string
    {
        $preco = $this->precoUnitario();

        if ($preco === null) {
            return null;
        }

        return Dinheiro::multiplicar($preco, $this->quantidade);
    }

    public function subtotalFormatado(): ?string
    {
        $subtotal = $this->subtotal();

        return $subtotal === null ? null : Dinheiro::formatarBrl($subtotal);
    }

    public function podeAlterarQuantidade(): bool
    {
        return $this->produto !== null
            && $this->produto->estaDisponivel()
            && in_array($this->cor, $this->produto->coresExibidas(), true);
    }

    public function entraNoTotal(): bool
    {
        return $this->situacao === self::SITUACAO_DISPONIVEL && $this->subtotal() !== null;
    }

    public function urlCapa(): ?string
    {
        return $this->produto?->urlCapa();
    }

    public function podeAbrirDetalhes(): bool
    {
        return $this->produto !== null;
    }

    public function estaIndisponivel(): bool
    {
        return $this->situacao === self::SITUACAO_INDISPONIVEL;
    }

    public function precisaAjuste(): bool
    {
        return $this->situacao === self::SITUACAO_AJUSTAR;
    }

    public function rotuloCor(): string
    {
        return CoresProduto::rotulo($this->cor);
    }
}
