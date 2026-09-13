<?php

namespace App\Support;

final class Cep
{
    /**
     * @var list<string>
     */
    public const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public static function normalizar(string $cep): string
    {
        $digitos = preg_replace('/\D+/', '', $cep) ?? '';
        $digitos = substr($digitos, 0, 8);

        return str_pad($digitos, 8, '0', STR_PAD_LEFT);
    }

    public static function formatar(string $cep): string
    {
        $normalizado = self::normalizar($cep);

        return substr($normalizado, 0, 5).'-'.substr($normalizado, 5);
    }

    public static function ehValido(string $cep): bool
    {
        return preg_match('/^\d{8}$/', self::normalizar($cep)) === 1;
    }
}
