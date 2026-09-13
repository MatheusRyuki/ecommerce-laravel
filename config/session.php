<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Driver de sessão padrão
    |--------------------------------------------------------------------------
    |
    | Driver que persiste a sessão nas requisições. Os nomes dos drivers são contrato do Laravel.
    |
    | Supported: "file", "cookie", "database", "memcached",
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Duração da sessão
    |--------------------------------------------------------------------------
    |
    | Minutos de inatividade até a sessão expirar. expire_on_close encerra ao fechar o navegador.
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Criptografia da sessão
    |--------------------------------------------------------------------------
    |
    | Se verdadeiro, os dados da sessão são criptografados antes de gravar.
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Arquivos de sessão
    |--------------------------------------------------------------------------
    |
    | Diretório dos arquivos quando o driver é file.
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Conexão da sessão
    |--------------------------------------------------------------------------
    |
    | Conexão database/redis usada pelo driver correspondente.
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Tabela da sessão
    |--------------------------------------------------------------------------
    |
    | Tabela usada pelo driver database. O nome sessions é o padrão do Laravel.
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Store de cache da sessão
    |--------------------------------------------------------------------------
    |
    | Store de cache dos backends dynamodb, memcached e redis.
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Limpeza periódica
    |--------------------------------------------------------------------------
    |
    | Probabilidade de varrer sessões antigas. Padrão: 2 em 100.
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Nome do cookie de sessão
    |--------------------------------------------------------------------------
    |
    | Nome do cookie criado pelo framework.
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Caminho do cookie
    |--------------------------------------------------------------------------
    |
    | Path HTTP do cookie. A chave path é contrato do Laravel; o valor padrão é /.
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Domínio do cookie
    |--------------------------------------------------------------------------
    |
    | Domínio em que o cookie de sessão é válido.
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Somente HTTPS
    |--------------------------------------------------------------------------
    |
    | Se verdadeiro, o cookie só é enviado em conexões HTTPS.
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | Somente HTTP
    |--------------------------------------------------------------------------
    |
    | Se verdadeiro, o JavaScript não acessa o cookie (HttpOnly).
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site
    |--------------------------------------------------------------------------
    |
    | Comportamento em requisições cross-site. Valores suportados pelo navegador: lax, strict, none.
    |
    | Supported: "lax", "strict", "none", null
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Cookies particionados
    |--------------------------------------------------------------------------
    |
    | Associa o cookie ao site de nível superior em contexto cross-site.
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Serialização da sessão
    |--------------------------------------------------------------------------
    |
    | Estratégia de serialização. json é o padrão seguro do Laravel.
    |
    | Supported: "json", "php"
    */

    'serialization' => 'json',

];
