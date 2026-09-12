<?php

namespace App\Support;

final class ProductColors
{
    /**
     * Allowed storefront colors (original-template/product_details.html).
     *
     * @var list<string>
     */
    public const VALUES = ['Red', 'Yellow', 'Blue', 'Green'];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::VALUES;
    }
}
