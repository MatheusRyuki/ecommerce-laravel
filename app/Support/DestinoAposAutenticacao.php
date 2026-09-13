<?php

namespace App\Support;

use App\Models\Usuario;

class DestinoAposAutenticacao
{
    public static function url(?Usuario $usuario = null, array $consulta = []): string
    {
        $usuario ??= auth()->user();
        $nome = $usuario?->administrador === true ? 'admin.produtos.listar' : 'dashboard';
        $url = route($nome, absolute: false);

        if ($consulta === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($consulta);
    }
}
