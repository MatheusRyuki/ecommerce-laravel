<?php

namespace App\Models;

use App\Support\CoresProduto;
use App\Support\Dinheiro;
use App\Support\DiscoArquivosProduto;
use Database\Factories\ProdutoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    /** @use HasFactory<ProdutoFactory> */
    use HasFactory;

    protected $table = 'produtos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'preco',
        'cores',
        'descricao_curta',
        'quantidade',
        'sku',
        'publicado',
        'categoria_id',
        'descricao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'cores' => 'array',
            'quantidade' => 'integer',
            'publicado' => 'boolean',
            'categoria_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return HasMany<ImagemProduto, $this>
     */
    public function imagens(): HasMany
    {
        return $this->hasMany(ImagemProduto::class, 'produto_id')->orderBy('posicao');
    }

    public function imagemCapa(): ?ImagemProduto
    {
        return $this->imagens->first();
    }

    public function urlCapa(): ?string
    {
        $capa = $this->imagemCapa();

        if ($capa === null || ! DiscoArquivosProduto::disco()->exists($capa->caminho)) {
            return null;
        }

        return $capa->url();
    }

    public function estaPublicado(): bool
    {
        return $this->publicado === true;
    }

    public function estaDisponivel(): bool
    {
        return $this->estaPublicado() && $this->quantidade > 0;
    }

    /**
     * @param  Builder<Produto>  $consulta
     * @return Builder<Produto>
     */
    public function scopePublicados(Builder $consulta): Builder
    {
        return $consulta->where('publicado', true);
    }

    public function precoFormatado(): string
    {
        return Dinheiro::formatarBrl((string) $this->preco);
    }

    /**
     * @return list<string>
     */
    public function coresExibidas(): array
    {
        $cores = [];

        foreach ($this->cores ?? [] as $cor) {
            if (! is_string($cor) || $cor === '') {
                continue;
            }

            $normalizada = CoresProduto::normalizar($cor);

            if (CoresProduto::ehPermitida($normalizada) && ! in_array($normalizada, $cores, true)) {
                $cores[] = $normalizada;
            }
        }

        return $cores;
    }
}
