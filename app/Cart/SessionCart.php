<?php

namespace App\Cart;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SessionCart
{
    public const SESSION_KEY = 'cart.items';

    public function __construct(private Session $session) {}

    /**
     * @return list<array{id: string, product_id: int, color: string, quantity: int}>
     */
    public function items(): array
    {
        return $this->normalizedItems();
    }

    public function totalQuantity(): int
    {
        return array_sum(array_column($this->normalizedItems(), 'quantity'));
    }

    public function add(int $productId, string $color, int $quantity): void
    {
        if ($quantity < 1) {
            throw new CannotAddToCartException(__('The quantity must be a positive integer.'));
        }

        $product = Product::query()->find($productId);

        if ($product === null || ! $product->isAvailable()) {
            throw new CannotAddToCartException(__('This product cannot be added to the cart.'));
        }

        if (! in_array($color, $product->displayColors(), true)) {
            throw new CannotAddToCartException(__('The selected color is not available for this product.'));
        }

        $items = $this->normalizedItems();
        $quantityAlreadyInCart = 0;

        foreach ($items as $item) {
            if ($item['product_id'] === $productId) {
                $quantityAlreadyInCart += $item['quantity'];
            }
        }

        if ($quantityAlreadyInCart + $quantity > $product->qty) {
            throw new CannotAddToCartException(__('The requested quantity exceeds the available stock.'));
        }

        $merged = false;

        foreach ($items as $index => $item) {
            if ($item['product_id'] === $productId && $item['color'] === $color) {
                $items[$index]['quantity'] = $item['quantity'] + $quantity;
                $merged = true;
                break;
            }
        }

        if (! $merged) {
            $items[] = [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'color' => $color,
                'quantity' => $quantity,
            ];
        }

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function updateQuantity(string $itemId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new CartException(__('The quantity must be a positive integer.'));
        }

        $items = $this->normalizedItems();
        $index = $this->indexOf($items, $itemId);
        $line = $items[$index];
        $product = Product::query()->find($line['product_id']);

        if ($product === null || ! $product->isAvailable() || ! in_array($line['color'], $product->displayColors(), true)) {
            throw new CartException(__('This cart item cannot be updated.'));
        }

        $others = 0;

        foreach ($items as $itemIndex => $item) {
            if ($itemIndex !== $index && $item['product_id'] === $line['product_id']) {
                $others += $item['quantity'];
            }
        }

        if ($quantity > $line['quantity'] && ($others + $quantity) > $product->qty) {
            throw new CartException(__('The requested quantity exceeds the available stock.'));
        }

        $items[$index]['quantity'] = $quantity;
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function remove(string $itemId): void
    {
        $items = $this->normalizedItems();
        $index = $this->indexOf($items, $itemId);
        unset($items[$index]);
        $this->session->put(self::SESSION_KEY, array_values($items));
    }

    /**
     * @param  Collection<int, CartLine>|null  $lines
     */
    public function productTotal(?Collection $lines = null): ?string
    {
        $lines ??= $this->lines();

        if ($lines->isEmpty()) {
            return '0.00';
        }

        if ($lines->contains(fn (CartLine $line): bool => ! $line->contributesToProductTotal())) {
            return null;
        }

        $subtotals = $lines
            ->map(fn (CartLine $line): string => $line->subtotal() ?? '0.00')
            ->all();

        return Money::add(...$subtotals);
    }

    /**
     * @param  list<array{id: string, product_id: int, color: string, quantity: int}>  $items
     */
    private function indexOf(array $items, string $itemId): int
    {
        foreach ($items as $index => $item) {
            if ($item['id'] === $itemId) {
                return $index;
            }
        }

        throw new CartItemNotFoundException(__('Cart item not found.'));
    }

    /**
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        $items = $this->normalizedItems();
        $productIds = array_values(array_unique(array_column($items, 'product_id')));
        $products = Product::query()
            ->with('images')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $requestedByProduct = [];

        foreach ($items as $item) {
            $requestedByProduct[$item['product_id']] = ($requestedByProduct[$item['product_id']] ?? 0) + $item['quantity'];
        }

        return collect($items)->map(function (array $item) use ($products, $requestedByProduct): CartLine {
            $product = $products->get($item['product_id']);

            return new CartLine(
                id: $item['id'],
                productId: $item['product_id'],
                color: $item['color'],
                quantity: $item['quantity'],
                product: $product,
                status: $this->statusFor($product, $item['color'], $requestedByProduct[$item['product_id']]),
            );
        })->values();
    }

    /**
     * @return list<array{id: string, product_id: int, color: string, quantity: int}>
     */
    private function normalizedItems(): array
    {
        $items = $this->session->get(self::SESSION_KEY, []);

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? null;
            $productId = $item['product_id'] ?? null;
            $color = $item['color'] ?? null;
            $quantity = $item['quantity'] ?? null;

            if (! is_string($id) || $id === '' || ! is_numeric($productId) || ! is_string($color) || $color === '' || ! is_numeric($quantity)) {
                continue;
            }

            $quantity = (int) $quantity;

            if ($quantity < 1) {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'product_id' => (int) $productId,
                'color' => $color,
                'quantity' => $quantity,
            ];
        }

        return $normalized;
    }

    private function statusFor(?Product $product, string $color, int $requestedForProduct): string
    {
        if ($product === null || ! $product->isAvailable()) {
            return CartLine::STATUS_UNAVAILABLE;
        }

        if (! in_array($color, $product->displayColors(), true) || $requestedForProduct > $product->qty) {
            return CartLine::STATUS_NEEDS_ADJUSTMENT;
        }

        return CartLine::STATUS_AVAILABLE;
    }
}
