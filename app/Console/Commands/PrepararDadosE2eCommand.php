<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Support\IsolamentoE2e;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class PrepararDadosE2eCommand extends Command
{
    protected $signature = 'e2e:preparar-usuario
        {--email= : E-mail}
        {--senha= : Senha}
        {--nome=Usuario E2E : Nome}
        {--administrador : Marca como administrador}
        {--nao-verificado : Deixa e-mail sem verificação}';

    protected $description = 'Cria um usuário somente no banco E2E.';

    public function handle(): int
    {
        IsolamentoE2e::garantir();

        $email = (string) $this->option('email');
        $senha = (string) $this->option('senha');

        if ($email === '' || $senha === '') {
            $this->error('Informe --email e --senha.');

            return self::FAILURE;
        }

        $usuario = Usuario::query()->create([
            'name' => (string) $this->option('nome'),
            'email' => $email,
            'password' => Hash::make($senha),
        ]);

        $usuario->administrador = (bool) $this->option('administrador');
        $usuario->email_verified_at = $this->option('nao-verificado') ? null : now();
        $usuario->save();

        $this->line((string) $usuario->id);

        return self::SUCCESS;
    }
}
