<?php

namespace App\Models;

use App\Support\CoresProduto;
use App\Support\Dinheiro;
use App\Support\DiscoArquivosProduto;
use Database\Factories\ProdutoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        ];
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

    public function estaDisponivel(): bool
    {
        return $this->quantidade > 0;
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
