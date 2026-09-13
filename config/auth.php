<?php

use App\Models\Usuario;

return [

    /*
    |--------------------------------------------------------------------------
    | Padrões de autenticação
    |--------------------------------------------------------------------------
    |
    | Guard e broker de redefinição de senha usados por padrão. Os nomes
    | web e users são o contrato configurável do Laravel.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards de autenticação
    |--------------------------------------------------------------------------
    |
    | Cada guard usa um provedor de usuários. O padrão web usa sessão e
    | Eloquent.
    |
    | Suportado: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provedores de usuário
    |--------------------------------------------------------------------------
    |
    | Como os usuários são lidos. O modelo da aplicação é Usuario, na
    | tabela users exigida pelo Breeze/Laravel.
    |
    | Suportados: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Usuario::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redefinição de senha
    |--------------------------------------------------------------------------
    |
    | Tabela de tokens, validade em minutos e intervalo mínimo entre
    | novos tokens. Os nomes da tabela e do broker são contrato do Laravel.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tempo da confirmação de senha
    |--------------------------------------------------------------------------
    |
    | Segundos até a confirmação de senha expirar. O padrão é três horas.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
