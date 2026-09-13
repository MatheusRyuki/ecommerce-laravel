<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FaixaFrete extends Model
{
    protected $table = 'faixas_frete';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cep_inicio',
        'cep_fim',
        'valor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<FaixaFrete>  $consulta
     */
    public function scopeSobrepostas(Builder $consulta, string $inicio, string $fim, ?int $exceto = null): Builder
    {
        $consulta->where('cep_inicio', '<=', $fim)->where('cep_fim', '>=', $inicio);

        if ($exceto !== null) {
            $consulta->whereKeyNot($exceto);
        }

        return $consulta;
    }

    public static function paraCep(string $cep): ?self
    {
        return static::query()
            ->where('cep_inicio', '<=', $cep)
            ->where('cep_fim', '>=', $cep)
            ->orderBy('cep_inicio')
            ->first();
    }
}
