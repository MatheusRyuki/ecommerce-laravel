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

    public static function subtrair(string $minuendo, string $subtraendo): string
    {
        return self::arredondarCentavos(bcsub($minuendo, $subtraendo, 6));
    }

    public static function minimo(string $a, string $b): string
    {
        return bccomp($a, $b, 6) <= 0 ? self::arredondarCentavos($a) : self::arredondarCentavos($b);
    }

    public static function percentualDe(string $base, string $percentual): string
    {
        $fator = bcdiv($percentual, '100', 8);

        return self::arredondarCentavos(bcmul($base, $fator, 8));
    }

    /**
     * Arredonda para duas casas com metade para cima (0,005 → 0,01).
     */
    public static function arredondarCentavos(string $valor): string
    {
        $negativo = str_starts_with($valor, '-');
        $absoluto = $negativo ? substr($valor, 1) : $valor;
        $centesimos = bcadd(bcmul($absoluto, '100', 8), '0.5', 0);
        $arredondado = bcdiv($centesimos, '100', 2);

        return $negativo && bccomp($arredondado, '0', 2) !== 0 ? '-'.$arredondado : $arredondado;
    }

    private static function casas(string $valor): string
    {
        return self::arredondarCentavos($valor);
    }
}
