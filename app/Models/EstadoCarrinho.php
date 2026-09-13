<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstadoCarrinho extends Model
{
    protected $table = 'estados_carrinho';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'cupom_codigo',
        'endereco_id',
        'chave_checkout',
        'revisao_checkout',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revisao_checkout' => 'array',
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
     * @return BelongsTo<Endereco, $this>
     */
    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class);
    }
}
