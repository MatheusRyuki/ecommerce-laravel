<?php

namespace App\Services;

use App\Cart\CarrinhoConta;
use App\Cart\LinhaCarrinho;
use App\Models\Cupom;
use App\Models\Endereco;
use App\Models\EstadoCarrinho;
use App\Models\FaixaFrete;
use App\Models\Usuario;
use App\Support\Dinheiro;
use Illuminate\Support\Collection;

final class CalculadoraCheckout
{
    /**
     * @param  Collection<int, LinhaCarrinho>  $linhas
     * @return array{
     *     valido: bool,
     *     motivo: ?string,
     *     subtotal: ?string,
     *     desconto: string,
     *     frete: ?string,
     *     total: ?string,
     *     cupom: ?Cupom,
     *     endereco: ?Endereco,
     *     faixa: ?FaixaFrete,
     *     assinatura: ?string
     * }
     */
    public function calcular(Usuario $usuario, Collection $linhas, CarrinhoConta $carrinho): array
    {
        $vazio = [
            'valido' => false,
            'motivo' => null,
            'subtotal' => null,
            'desconto' => '0.00',
            'frete' => null,
            'total' => null,
            'cupom' => null,
            'endereco' => null,
            'faixa' => null,
            'assinatura' => null,
        ];

        if ($linhas->isEmpty()) {
            return array_merge($vazio, ['motivo' => 'O carrinho está vazio.']);
        }

        if ($linhas->contains(fn (LinhaCarrinho $linha): bool => ! $linha->entraNoTotal())) {
            return array_merge($vazio, ['motivo' => 'Ajuste ou remova os itens indisponíveis antes de continuar.']);
        }

        $subtotal = $carrinho->totalProdutos($linhas);

        if ($subtotal === null) {
            return array_merge($vazio, ['motivo' => 'Não foi possível calcular o total dos produtos.']);
        }

        $estado = $carrinho->estado();
        $cupom = $this->cupomDoEstado($estado);
        $desconto = '0.00';

        if ($estado->cupom_codigo) {
            if ($cupom === null || ! $cupom->estaUtilizavel()) {
                return array_merge($vazio, [
                    'motivo' => 'O cupom informado não é mais válido. Revise os valores.',
                    'subtotal' => $subtotal,
                ]);
            }

            $desconto = $cupom->descontoSobre($subtotal);
        }

        $endereco = $estado->endereco_id
            ? Endereco::query()->where('usuario_id', $usuario->id)->whereKey($estado->endereco_id)->first()
            : Endereco::query()->where('usuario_id', $usuario->id)->where('padrao', true)->first();

        if ($endereco === null) {
            return array_merge($vazio, [
                'motivo' => 'Cadastre um endereço de entrega para continuar.',
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'cupom' => $cupom,
            ]);
        }

        $faixa = FaixaFrete::paraCep($endereco->cep);

        if ($faixa === null) {
            return array_merge($vazio, [
                'motivo' => 'Não há entrega para o CEP '.$endereco->cepFormatado().'.',
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'cupom' => $cupom,
                'endereco' => $endereco,
            ]);
        }

        $frete = Dinheiro::arredondarCentavos((string) $faixa->valor);
        $base = Dinheiro::subtrair($subtotal, $desconto);
        $total = Dinheiro::somar($base, $frete);

        $assinatura = hash('sha256', json_encode([
            $linhas->map(fn (LinhaCarrinho $l): array => [$l->idProduto, $l->cor, $l->quantidade, $l->precoUnitario()])->all(),
            $subtotal,
            $desconto,
            $frete,
            $endereco->id,
            $cupom?->codigo,
        ], JSON_THROW_ON_ERROR));

        return [
            'valido' => true,
            'motivo' => null,
            'subtotal' => $subtotal,
            'desconto' => $desconto,
            'frete' => $frete,
            'total' => $total,
            'cupom' => $cupom,
            'endereco' => $endereco,
            'faixa' => $faixa,
            'assinatura' => $assinatura,
        ];
    }

    private function cupomDoEstado(EstadoCarrinho $estado): ?Cupom
    {
        if ($estado->cupom_codigo === null || $estado->cupom_codigo === '') {
            return null;
        }

        return Cupom::query()->where('codigo', Cupom::normalizarCodigo($estado->cupom_codigo))->first();
    }
}
