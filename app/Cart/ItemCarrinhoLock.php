<?php

namespace App\Cart;

use App\Models\ItemCarrinho;

final class ItemCarrinhoLock
{
    public static function travar(int $usuarioId): void
    {
        ItemCarrinho::query()->where('usuario_id', $usuarioId)->lockForUpdate()->get();
    }
}
