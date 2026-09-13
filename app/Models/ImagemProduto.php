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

    protected $table = 'product_images';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'path',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Produto, $this>
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'product_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function urlDisponivel(): ?string
    {
        if (! Storage::disk('public')->exists($this->path)) {
            return null;
        }

        return $this->url();
    }
}
