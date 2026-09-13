<?php

namespace App\Support;

final class CoresProduto
{
    /**
     * @var list<string>
     */
    public const VALORES = ['Vermelho', 'Amarelo', 'Azul', 'Verde'];

    /**
     * Valores gravados antes da normalização do domínio.
     *
     * @var array<string, string>
     */
    private const LEGADO = [
        'Red' => 'Vermelho',
        'Yellow' => 'Amarelo',
        'Blue' => 'Azul',
        'Green' => 'Verde',
    ];

    /**
     * @return list<string>
     */
    public static function todas(): array
    {
        return self::VALORES;
    }

    public static function normalizar(string $valor): string
    {
        return self::LEGADO[$valor] ?? $valor;
    }

    public static function rotulo(string $valor): string
    {
        $normalizado = self::normalizar($valor);

        return in_array($normalizado, self::VALORES, true) ? $normalizado : $valor;
    }

    public static function ehPermitida(string $valor): bool
    {
        return in_array(self::normalizar($valor), self::VALORES, true);
    }
}
