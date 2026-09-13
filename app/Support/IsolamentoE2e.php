<?php

namespace App\Support;

use RuntimeException;

final class IsolamentoE2e
{
    public const BANCO = 'ecommerce_e2e';

    public const DIRETORIO_RELATIVO = 'storage/e2e';

    /**
     * @return array<string, mixed>
     */
    public static function diagnostico(): array
    {
        $raiz = self::raizAbsoluta();
        $marcador = self::caminhoMarcador();
        $conteudoMarcador = is_file($marcador) ? trim((string) file_get_contents($marcador)) : null;

        return [
            'ambiente' => app()->environment(),
            'banco' => self::bancoAtual(),
            'usuario_banco' => (string) config('database.connections.'.config('database.default').'.username'),
            'url' => (string) config('app.url'),
            'disco_produtos' => DiscoArquivosProduto::nome(),
            'raiz_disco_produtos' => DiscoArquivosProduto::disco()->path(''),
            'diretorio_e2e' => $raiz,
            'sessao_arquivos' => (string) config('session.files'),
            'cookie_sessao' => (string) config('session.cookie'),
            'cache' => (string) config('cache.default'),
            'views_compiladas' => (string) config('view.compiled'),
            'correio' => self::caminhoCorreio(),
            'marcador' => $conteudoMarcador,
        ];
    }

    public static function garantir(): void
    {
        if (! app()->environment('e2e')) {
            throw new RuntimeException('Operação E2E recusada: APP_ENV não é e2e.');
        }

        if (self::bancoAtual() !== self::BANCO) {
            throw new RuntimeException('Operação E2E recusada: o banco atual não é '.self::BANCO.'.');
        }

        $usuario = (string) config('database.connections.'.config('database.default').'.username');

        if ($usuario === 'sail' || $usuario === 'root') {
            throw new RuntimeException('Operação E2E recusada: o usuário do banco precisa ser restrito a '.self::BANCO.'.');
        }

        $raiz = self::raizAbsoluta();

        if (! str_ends_with($raiz, DIRECTORY_SEPARATOR.self::DIRETORIO_RELATIVO)
            && ! str_ends_with($raiz, '/'.self::DIRETORIO_RELATIVO)) {
            throw new RuntimeException('Operação E2E recusada: diretório-base fora de storage/e2e.');
        }

        $disco = str_replace('\\', '/', rtrim(DiscoArquivosProduto::disco()->path(''), '/\\'));
        $raizNormalizada = str_replace('\\', '/', rtrim($raiz, '/\\'));

        if (! str_starts_with($disco, $raizNormalizada.'/')) {
            throw new RuntimeException('Operação E2E recusada: disco de produtos fora de storage/e2e.');
        }
    }

    public static function bancoAtual(): string
    {
        $padrao = (string) config('database.default');

        return (string) config('database.connections.'.$padrao.'.database');
    }

    public static function raizAbsoluta(): string
    {
        return storage_path('e2e');
    }

    public static function caminhoMarcador(): string
    {
        return self::raizAbsoluta().'/marcador.txt';
    }

    public static function caminhoCorreio(): string
    {
        return storage_path('e2e/correio/mensagens.log');
    }

    public static function tokenValido(?string $recebido): bool
    {
        $esperado = (string) config('app.token_e2e');

        return $esperado !== '' && is_string($recebido) && hash_equals($esperado, $recebido);
    }
}
