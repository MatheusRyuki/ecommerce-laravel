<?php

namespace App\Models;

use App\Support\Dinheiro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Cupom extends Model
{
    public const TIPO_FIXO = 'fixo';

    public const TIPO_PERCENTUAL = 'percentual';

    protected $table = 'cupons';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'tipo',
        'valor',
        'valido_de',
        'valido_ate',
        'ativo',
        'uso_unico',
        'consumido_em',
        'pedido_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'valido_de' => 'datetime',
            'valido_ate' => 'datetime',
            'ativo' => 'boolean',
            'uso_unico' => 'boolean',
            'consumido_em' => 'datetime',
        ];
    }

    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(trim($codigo), 'UTF-8');
    }

    public function estaUtilizavel(?Carbon $agora = null): bool
    {
        $agora ??= now();

        if (! $this->ativo) {
            return false;
        }

        if ($this->uso_unico && $this->consumido_em !== null) {
            return false;
        }

        if ($this->valido_de !== null && $agora->lt($this->valido_de)) {
            return false;
        }

        if ($this->valido_ate !== null && $agora->gt($this->valido_ate)) {
            return false;
        }

        return true;
    }

    public function descontoSobre(string $subtotalProdutos): string
    {
        if (bccomp($subtotalProdutos, '0', 2) <= 0) {
            return '0.00';
        }

        $bruto = $this->tipo === self::TIPO_PERCENTUAL
            ? Dinheiro::percentualDe($subtotalProdutos, (string) $this->valor)
            : Dinheiro::arredondarCentavos((string) $this->valor);

        return Dinheiro::minimo($bruto, $subtotalProdutos);
    }

    public function rotuloTipo(): string
    {
        return match ($this->tipo) {
            self::TIPO_FIXO => 'Valor fixo (BRL)',
            self::TIPO_PERCENTUAL => 'Percentual',
            default => $this->tipo,
        };
    }

    public function rotuloAtivo(): string
    {
        return $this->ativo ? 'Ativo' : 'Inativo';
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
