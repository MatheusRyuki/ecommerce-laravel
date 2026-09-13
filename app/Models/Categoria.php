<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $table = 'categorias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'slug',
    ];

    protected static function booted(): void
    {
        static::saving(function (Categoria $categoria): void {
            if ($categoria->slug === null || $categoria->slug === '') {
                $categoria->slug = Str::slug($categoria->nome);
            }
        });
    }

    /**
     * @return HasMany<Produto, $this>
     */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}
