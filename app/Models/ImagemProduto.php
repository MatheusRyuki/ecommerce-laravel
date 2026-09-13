<?php

namespace App\Models;

use Database\Factories\ImagemProdutoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ImagemProduto extends Model
{
    /** @use HasFactory<ImagemProdutoFactory> */
    use HasFactory;

    protected $table = 'imagens_produto';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'produto_id',
        'caminho',
        'posicao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posicao' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Produto, $this>
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->caminho);
    }

    public function urlDisponivel(): ?string
    {
        if (! Storage::disk('public')->exists($this->caminho)) {
            return null;
        }

        return $this->url();
    }
}
