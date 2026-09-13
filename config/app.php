<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nome da aplicação
    |--------------------------------------------------------------------------
    |
    | Nome exibido em notificações e demais trechos da interface que pedem
    | o nome da aplicação.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Ambiente da aplicação
    |--------------------------------------------------------------------------
    |
    | Ambiente atual (local, production, etc.). Ajuste no arquivo ".env".
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Modo de depuração
    |--------------------------------------------------------------------------
    |
    | Com debug ativo, erros mostram rastreamento detalhado. Desligado, a
    | aplicação exibe uma página genérica.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL da aplicação
    |--------------------------------------------------------------------------
    |
    | URL raiz usada pelo Artisan ao gerar links. Deve apontar para o host
    | local deste projeto (por exemplo http://localhost:8002).
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Fuso horário
    |--------------------------------------------------------------------------
    |
    | Fuso usado pelas funções de data e hora do PHP. O padrão do Laravel
    | é UTC.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Idioma
    |--------------------------------------------------------------------------
    |
    | Locale padrão das traduções do Laravel. Este projeto usa pt_BR.
    |
    */

    'locale' => env('APP_LOCALE', 'pt_BR'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'pt_BR'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),

    /*
    |--------------------------------------------------------------------------
    | Chave de criptografia
    |--------------------------------------------------------------------------
    |
    | Chave aleatória usada pelos serviços de criptografia do Laravel.
    | Defina APP_KEY antes de usar a aplicação.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo de manutenção
    |--------------------------------------------------------------------------
    |
    | Driver que controla o modo de manutenção. "cache" permite coordenar
    | várias instâncias.
    |
    | Drivers suportados: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
