<?php

namespace App\Cart;

use App\Models\Product;
use App\Support\Money;

final readonly class CartLine
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_NEEDS_ADJUSTMENT = 'needs_adjustment';

    public function __construct(
        public string $id,
        public int $productId,
        public string $color,
        public int $quantity,
        public ?Product $product,
        public string $status,
    ) {}

    public function name(): string
    {
        return $this->product?->name ?? __('Unavailable item');
    }

    public function unitPrice(): ?string
    {
        if ($this->product === null) {
            return null;
        }

        return (string) $this->product->price;
    }

    public function formattedUnitPrice(): ?string
    {
        $unitPrice = $this->unitPrice();

        return $unitPrice === null ? null : Money::formatBrl($unitPrice);
    }

    public function subtotal(): ?string
    {
        $unitPrice = $this->unitPrice();

        if ($unitPrice === null) {
            return null;
        }

        return Money::multiply($unitPrice, $this->quantity);
    }

    public function formattedSubtotal(): ?string
    {
        $subtotal = $this->subtotal();

        return $subtotal === null ? null : Money::formatBrl($subtotal);
    }

    public function canChangeQuantity(): bool
    {
        return $this->product !== null
            && $this->product->isAvailable()
            && in_array($this->color, $this->product->displayColors(), true);
    }

    public function contributesToProductTotal(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->subtotal() !== null;
    }

    public function coverUrl(): ?string
    {
        return $this->product?->coverUrl();
    }

    public function canOpenDetails(): bool
    {
        return $this->product !== null;
    }

    public function isUnavailable(): bool
    {
        return $this->status === self::STATUS_UNAVAILABLE;
    }

    public function needsAdjustment(): bool
    {
        return $this->status === self::STATUS_NEEDS_ADJUSTMENT;
    }
}
