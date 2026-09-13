<?php

namespace App\Models;

use App\Support\Dinheiro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    public const STATUS_AGUARDANDO_PAGAMENTO = 'aguardando_pagamento';

    protected $table = 'pedidos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'codigo',
        'status',
        'subtotal_produtos',
        'desconto',
        'frete',
        'total',
        'cupom_codigo',
        'endereco_entrega',
        'chave_idempotencia',
        'observacao_pagamento',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_produtos' => 'decimal:2',
            'desconto' => 'decimal:2',
            'frete' => 'decimal:2',
            'total' => 'decimal:2',
            'endereco_entrega' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * @return HasMany<ItemPedido, $this>
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemPedido::class);
    }

    public function totalFormatado(): string
    {
        return Dinheiro::formatarBrl((string) $this->total);
    }
}
