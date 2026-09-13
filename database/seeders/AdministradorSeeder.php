<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AdministradorSeeder extends Seeder
{
    private const NOME_PADRAO = 'Administrador';

    private const EMAIL_PADRAO = 'admin@example.test';

    public function run(): void
    {
        $nome = $this->resolverNome();
        $email = $this->resolverEmail();
        $existente = Usuario::query()->where('email', $email)->first();

        if ($existente !== null) {
            if ($existente->administrador !== true) {
                throw new RuntimeException(
                    "O e-mail {$email} já pertence a um usuário comum. O seeder não alterou esse registro."
                );
            }

            $this->command?->info('Administrador já existe. Nenhuma alteração foi feita.');

            return;
        }

        $senha = $this->resolverSenhaParaCriacao();

        $administrador = new Usuario;
        $administrador->forceFill([
            'name' => $nome,
            'email' => $email,
            'password' => $senha,
            'administrador' => true,
        ])->save();

        $this->command?->info('Administrador criado. Consulte ADMIN_PASSWORD no arquivo .env local.');
    }

    private function resolverNome(): string
    {
        $nome = trim((string) config('admin.name'));

        return $nome !== '' ? $nome : self::NOME_PADRAO;
    }

    private function resolverEmail(): string
    {
        $email = strtolower(trim((string) config('admin.email')));
        $email = $email !== '' ? $email : self::EMAIL_PADRAO;

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('ADMIN_EMAIL precisa ser um endereço de e-mail válido.');
        }

        return $email;
    }

    private function resolverSenhaParaCriacao(): string
    {
        $senha = (string) config('admin.password');

        if ($senha !== '') {
            return $senha;
        }

        $senha = Str::password(32, symbols: false);

        if (! app()->environment('testing')) {
            $this->persistirSenhaNoEnv($senha);
        }

        config(['admin.password' => $senha]);

        return $senha;
    }

    private function persistirSenhaNoEnv(string $senha): void
    {
        $caminhoEnv = base_path('.env');

        if (! File::isFile($caminhoEnv) || ! File::isWritable($caminhoEnv)) {
            throw new RuntimeException('Não foi possível gravar ADMIN_PASSWORD no .env local.');
        }

        $conteudo = File::get($caminhoEnv);
        $linha = 'ADMIN_PASSWORD='.$this->formatarValorEnv($senha);

        if (preg_match('/^ADMIN_PASSWORD=.*$/m', $conteudo) === 1) {
            $conteudo = preg_replace('/^ADMIN_PASSWORD=.*$/m', $linha, $conteudo, 1);
        } else {
            $conteudo = rtrim($conteudo)."\n".$linha."\n";
        }

        File::put($caminhoEnv, $conteudo);
    }

    private function formatarValorEnv(string $valor): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $valor).'"';
    }
}
