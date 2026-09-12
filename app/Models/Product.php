<?php

namespace App\Models;

use App\Support\ProductColors;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

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
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function coverImage(): ?ProductImage
    {
        return $this->images->first();
    }

    public function coverUrl(): ?string
    {
        $cover = $this->coverImage();

        if ($cover === null || ! Storage::disk('public')->exists($cover->path)) {
            return null;
        }

        return $cover->url();
    }

    public function isAvailable(): bool
    {
        return $this->qty > 0;
    }

    public function formattedPrice(): string
    {
        return 'R$ '.number_format((float) $this->price, 2, ',', '.');
    }

    /**
     * @return list<string>
     */
    public function displayColors(): array
    {
        $allowed = ProductColors::all();

        return array_values(array_filter(
            $this->colors ?? [],
            fn (mixed $color): bool => is_string($color) && in_array($color, $allowed, true),
        ));
    }
}
