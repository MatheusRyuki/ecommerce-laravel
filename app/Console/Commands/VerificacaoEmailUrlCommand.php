<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

#[Signature('verificacao:url {email : E-mail da conta}')]
#[Description('Gera o link assinado de verificação de e-mail para uso local (não altera a senha).')]
class VerificacaoEmailUrlCommand extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $usuario = Usuario::query()->where('email', $email)->first();

        if ($usuario === null) {
            $this->error('Conta não encontrada.');

            return self::FAILURE;
        }

        if ($usuario->hasVerifiedEmail()) {
            $this->info('Esta conta já está verificada.');

            return self::SUCCESS;
        }

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $usuario->id, 'hash' => sha1($usuario->email)],
        );

        $this->line($url);
        $this->comment('Abra o link enquanto estiver autenticado com essa conta, ou copie a URL no navegador após entrar.');

        return self::SUCCESS;
    }
}
