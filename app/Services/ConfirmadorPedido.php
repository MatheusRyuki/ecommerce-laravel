<?php

namespace App\Services;

use App\Cart\CarrinhoConta;
use App\Models\Cupom;
use App\Models\ItemPedido;
use App\Models\MovimentacaoEstoque;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ConfirmadorPedido
{
    public function __construct(
        private CalculadoraCheckout $calculadora,
        private RegistradorEstoque $estoque,
    ) {}

    public function confirmar(Usuario $usuario, string $chave, string $assinaturaEsperada): Pedido
    {
        return DB::transaction(function () use ($usuario, $chave, $assinaturaEsperada): Pedido {
            $existente = Pedido::query()
                ->where('usuario_id', $usuario->id)
                ->where('chave_idempotencia', $chave)
                ->lockForUpdate()
                ->first();

            if ($existente !== null) {
                return $existente->load('itens');
            }

            $carrinho = new CarrinhoConta($usuario);
            $linhas = $carrinho->linhas();
            $calculo = $this->calculadora->calcular($usuario, $linhas, $carrinho);

            if (! $calculo['valido'] || $calculo['assinatura'] !== $assinaturaEsperada) {
                throw new RuntimeException($calculo['motivo'] ?? 'Os valores do pedido mudaram. Revise antes de confirmar.');
            }

            $idsProdutos = $linhas->pluck('idProduto')->unique()->sort()->values();
            $produtos = Produto::query()->whereIn('id', $idsProdutos)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($linhas as $linha) {
                $produto = $produtos->get($linha->idProduto);

                if ($produto === null || ! $produto->estaPublicado() || (string) $produto->preco !== $linha->precoUnitario()) {
                    throw new RuntimeException('Os valores do pedido mudaram. Revise antes de confirmar.');
                }
            }

            $porProduto = [];
            foreach ($linhas as $linha) {
                $porProduto[$linha->idProduto] = ($porProduto[$linha->idProduto] ?? 0) + $linha->quantidade;
            }

            foreach ($porProduto as $id => $qty) {
                $produto = $produtos->get($id);
                if ($produto === null || $produto->quantidade < $qty) {
                    throw new RuntimeException('O estoque mudou. Revise o carrinho antes de confirmar.');
                }
            }

            $cupom = $calculo['cupom'];

            if ($cupom !== null) {
                $cupom = Cupom::query()->whereKey($cupom->id)->lockForUpdate()->first();

                if ($cupom === null || ! $cupom->estaUtilizavel()) {
                    throw new RuntimeException('O cupom informado não é mais válido. Revise os valores.');
                }
            }

            $pedido = Pedido::query()->create([
                'usuario_id' => $usuario->id,
                'codigo' => $this->codigo(),
                'status' => Pedido::STATUS_AGUARDANDO_PAGAMENTO,
                'subtotal_produtos' => $calculo['subtotal'],
                'desconto' => $calculo['desconto'],
                'frete' => $calculo['frete'],
                'total' => $calculo['total'],
                'cupom_codigo' => $cupom?->codigo,
                'endereco_entrega' => $calculo['endereco']->paraSnapshot(),
                'chave_idempotencia' => $chave,
                'observacao_pagamento' => 'Nenhum pagamento foi processado. O pedido permanece aguardando pagamento.',
            ]);

            foreach ($linhas as $linha) {
                ItemPedido::query()->create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $linha->idProduto,
                    'nome' => $linha->nome(),
                    'sku' => $linha->produto?->sku ?? '',
                    'cor' => $linha->cor,
                    'quantidade' => $linha->quantidade,
                    'preco_unitario' => $linha->precoUnitario(),
                    'subtotal' => $linha->subtotal(),
                ]);
            }

            foreach ($porProduto as $id => $qty) {
                $produto = $produtos->get($id);
                $this->estoque->aplicarDelta(
                    $produto,
                    -$qty,
                    MovimentacaoEstoque::TIPO_PEDIDO,
                    'Baixa do pedido '.$pedido->codigo.'.',
                    $usuario,
                    $pedido->id,
                );
            }

            if ($cupom !== null) {
                $cupom->consumido_em = now();
                $cupom->pedido_id = $pedido->id;
                $cupom->save();
            }

            $carrinho->limpar();
            $estado = $carrinho->estado();
            $estado->cupom_codigo = null;
            $estado->chave_checkout = null;
            $estado->revisao_checkout = null;
            $estado->save();

            return $pedido->load('itens');
        });
    }

    private function codigo(): string
    {
        do {
            $codigo = 'PED-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (Pedido::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }
}
