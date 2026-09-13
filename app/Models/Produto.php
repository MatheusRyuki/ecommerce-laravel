<?php

namespace App\Models;

use App\Support\CoresProduto;
use Database\Factories\ProdutoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Produto extends Model
{
    /** @use HasFactory<ProdutoFactory> */
    use HasFactory;

    protected $table = 'products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'price',
        'colors',
        'short_description',
        'qty',
        'sku',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'colors' => 'array',
            'qty' => 'integer',
        ];
    }

    /**
     * @return HasMany<ImagemProduto, $this>
     */
    public function imagens(): HasMany
    {
        return $this->hasMany(ImagemProduto::class, 'product_id')->orderBy('position');
    }

    public function imagemCapa(): ?ImagemProduto
    {
        return $this->imagens->first();
    }

    public function urlCapa(): ?string
    {
        $capa = $this->imagemCapa();

        if ($capa === null || ! Storage::disk('public')->exists($capa->path)) {
            return null;
        }

        return $capa->url();
    }

    public function estaDisponivel(): bool
    {
        return $this->qty > 0;
    }

    public function precoFormatado(): string
    {
        return 'R$ '.number_format((float) $this->price, 2, ',', '.');
    }

    /**
     * @return list<string>
     */
    public function coresExibidas(): array
    {
        $permitidas = CoresProduto::todas();

        return array_values(array_filter(
            $this->colors ?? [],
            fn (mixed $cor): bool => is_string($cor) && in_array($cor, $permitidas, true),
        ));
    }
}
