<?php

namespace App\Support;

final class Dinheiro
{
    public static function multiplicar(string $valor, int $quantidade): string
    {
        return self::casas(bcmul($valor, (string) $quantidade, 4));
    }

    public static function somar(string ...$valores): string
    {
        $soma = '0';

        foreach ($valores as $valor) {
            $soma = bcadd($soma, $valor, 4);
        }

        return self::casas($soma);
    }

    public static function formatarBrl(string $valor): string
    {
        $normalizado = self::casas($valor);
        $negativo = str_starts_with($normalizado, '-');

        if ($negativo) {
            $normalizado = substr($normalizado, 1);
        }

        [$inteiro, $fracao] = array_pad(explode('.', $normalizado, 2), 2, '00');
        $agrupado = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $inteiro) ?? $inteiro;

        return ($negativo ? '-' : '').'R$ '.$agrupado.','.$fracao;
    }

    private static function casas(string $valor): string
    {
        return bcadd($valor, '0', 2);
    }
}
