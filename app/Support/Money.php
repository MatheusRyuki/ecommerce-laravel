<?php

namespace App\Support;

final class Money
{
    public static function multiply(string $amount, int $quantity): string
    {
        return self::scale(bcmul($amount, (string) $quantity, 4));
    }

    public static function add(string ...$amounts): string
    {
        $sum = '0';

        foreach ($amounts as $amount) {
            $sum = bcadd($sum, $amount, 4);
        }

        return self::scale($sum);
    }

    public static function formatBrl(string $amount): string
    {
        $normalized = self::scale($amount);
        $negative = str_starts_with($normalized, '-');

        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '00');
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole) ?? $whole;

        return ($negative ? '-' : '').'R$ '.$grouped.','.$fraction;
    }

    private static function scale(string $amount): string
    {
        return bcadd($amount, '0', 2);
    }
}
