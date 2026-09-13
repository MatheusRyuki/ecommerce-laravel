<?php

namespace App\Services;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistradorEstoque
{
    public function definir(
        Produto $produto,
        int $quantidadeFinal,
        string $tipo,
        string $motivo,
        ?Usuario $responsavel = null,
        ?int $pedidoId = null,
    ): MovimentacaoEstoque {
        if ($quantidadeFinal < 0) {
            throw new InvalidArgumentException('O estoque não pode ficar negativo.');
        }

        return DB::transaction(function () use ($produto, $quantidadeFinal, $tipo, $motivo, $responsavel, $pedidoId): MovimentacaoEstoque {
            $travado = Produto::query()->whereKey($produto->id)->lockForUpdate()->firstOrFail();
            $anterior = $travado->quantidade;

            $travado->quantidade = $quantidadeFinal;
            $travado->save();

            return MovimentacaoEstoque::query()->create([
                'produto_id' => $travado->id,
                'usuario_id' => $responsavel?->id,
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'quantidade_anterior' => $anterior,
                'quantidade_final' => $quantidadeFinal,
                'delta' => $quantidadeFinal - $anterior,
                'motivo' => $motivo,
            ]);
        });
    }

    public function aplicarDelta(
        Produto $produto,
        int $delta,
        string $tipo,
        string $motivo,
        ?Usuario $responsavel = null,
        ?int $pedidoId = null,
    ): MovimentacaoEstoque {
        return DB::transaction(function () use ($produto, $delta, $tipo, $motivo, $responsavel, $pedidoId): MovimentacaoEstoque {
            $travado = Produto::query()->whereKey($produto->id)->lockForUpdate()->firstOrFail();
            $anterior = $travado->quantidade;
            $final = $anterior + $delta;

            if ($final < 0) {
                throw new InvalidArgumentException('O estoque não pode ficar negativo.');
            }

            $travado->quantidade = $final;
            $travado->save();

            return MovimentacaoEstoque::query()->create([
                'produto_id' => $travado->id,
                'usuario_id' => $responsavel?->id,
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'quantidade_anterior' => $anterior,
                'quantidade_final' => $final,
                'delta' => $delta,
                'motivo' => $motivo,
            ]);
        });
    }
}
