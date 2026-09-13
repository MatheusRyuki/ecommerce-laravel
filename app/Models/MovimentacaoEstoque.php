<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    public const TIPO_ENTRADA = 'entrada';

    public const TIPO_SAIDA = 'saida';

    public const TIPO_AJUSTE = 'ajuste';

    public const TIPO_PEDIDO = 'pedido';

    public const TIPO_DUPLICACAO = 'duplicacao';

    public const TIPO_POSICAO_INICIAL = 'posicao_inicial';

    protected $table = 'movimentacoes_estoque';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'produto_id',
        'usuario_id',
        'pedido_id',
        'tipo',
        'quantidade_anterior',
        'quantidade_final',
        'delta',
        'motivo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantidade_anterior' => 'integer',
            'quantidade_final' => 'integer',
            'delta' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Produto, $this>
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
