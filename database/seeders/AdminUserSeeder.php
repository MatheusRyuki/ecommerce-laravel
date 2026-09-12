<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    private const DEFAULT_NAME = 'Administrador';

    private const DEFAULT_EMAIL = 'admin@example.test';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = $this->resolveName();
        $email = $this->resolveEmail();
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            if ($existing->is_admin !== true) {
                throw new RuntimeException(
                    "O e-mail {$email} já pertence a um usuário comum. O seeder não alterou esse registro."
                );
            }

            $this->command?->info('Administrador já existe. Nenhuma alteração foi feita.');

            return;
        }

        $password = $this->resolvePasswordForCreation();

        $admin = new User;
        $admin->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ])->save();

        $this->command?->info('Administrador criado. Consulte ADMIN_PASSWORD no arquivo .env local.');
    }

    private function resolveName(): string
    {
        $name = trim((string) config('admin.name'));

        return $name !== '' ? $name : self::DEFAULT_NAME;
    }

    private function resolveEmail(): string
    {
        $email = strtolower(trim((string) config('admin.email')));
        $email = $email !== '' ? $email : self::DEFAULT_EMAIL;

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('ADMIN_EMAIL precisa ser um endereço de e-mail válido.');
        }

        return $email;
    }

    private function resolvePasswordForCreation(): string
    {
        $password = (string) config('admin.password');

        if ($password !== '') {
            return $password;
        }

        $password = Str::password(32, symbols: false);

        if (! app()->environment('testing')) {
            $this->persistPasswordToEnv($password);
        }

        config(['admin.password' => $password]);

        return $password;
    }

    private function persistPasswordToEnv(string $password): void
    {
        $envPath = base_path('.env');

        if (! File::isFile($envPath) || ! File::isWritable($envPath)) {
            throw new RuntimeException('Não foi possível gravar ADMIN_PASSWORD no .env local.');
        }

        $contents = File::get($envPath);
        $line = 'ADMIN_PASSWORD='.$this->formatEnvValue($password);

        if (preg_match('/^ADMIN_PASSWORD=.*$/m', $contents) === 1) {
            $contents = preg_replace('/^ADMIN_PASSWORD=.*$/m', $line, $contents, 1);
        } else {
            $contents = rtrim($contents)."\n".$line."\n";
        }

        File::put($envPath, $contents);
    }

    private function formatEnvValue(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }
}
