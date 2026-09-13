<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Credenciais do administrador local
    |--------------------------------------------------------------------------
    |
    | Usadas pelo AdministradorSeeder. Não defina senha no .env.example.
    | A senha gerada na primeira execução é gravada apenas no .env local.
    |
    */

    'name' => env('ADMIN_NAME'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),

];
