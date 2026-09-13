<?php

namespace App\Support;

final class CoresProduto
{
    /**
     * Valores gravados no banco (opções do template original). Não alterar.
     *
     * @var list<string>
     */
    public const VALORES = ['Red', 'Yellow', 'Blue', 'Green'];

    /**
     * @return list<string>
     */
    public static function todas(): array
    {
        return self::VALORES;
    }

    public static function rotulo(string $valor): string
    {
        return match ($valor) {
            'Red' => 'Vermelho',
            'Yellow' => 'Amarelo',
            'Blue' => 'Azul',
            'Green' => 'Verde',
            default => $valor,
        };
    }
}
