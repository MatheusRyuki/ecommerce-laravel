<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\PerfilController;
use App\Models\Usuario;
use App\Services\ConfirmadorPedido;
use App\Support\IsolamentoE2e;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

require __DIR__.'/../../vendor/autoload.php';

$dir = (string) getenv('CONCORRENCIA_DIR');
$papel = (string) getenv('CONCORRENCIA_PAPEL');

if ($dir === '' || ! in_array($papel, ['a', 'b'], true)) {
    fwrite(STDERR, "CONCORRENCIA_DIR e CONCORRENCIA_PAPEL são obrigatórios.\n");
    exit(2);
}

$escrever = static function (string $nome, mixed $dados) use ($dir): void {
    file_put_contents($dir.'/'.$nome, json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
};

$esperar = static function (string $nome, float $limite) use ($dir): void {
    $caminho = $dir.'/'.$nome;
    $inicio = microtime(true);
    while (! is_file($caminho)) {
        if ((microtime(true) - $inicio) > $limite) {
            throw new RuntimeException('Timeout à espera de '.$nome);
        }
        usleep(20_000);
    }
};

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    IsolamentoE2e::garantir();
    $conexao = DB::selectOne('select database() as db, current_user() as usuario, connection_id() as id');
    if (($conexao->db ?? '') !== IsolamentoE2e::BANCO) {
        throw new RuntimeException('Filho conectado ao banco '.($conexao->db ?? 'null'));
    }
    if (str_contains((string) $conexao->usuario, 'sail') || str_starts_with((string) $conexao->usuario, 'root@')) {
        throw new RuntimeException('Filho usando usuário de banco proibido: '.$conexao->usuario);
    }

    $escrever($papel.'-conexao.json', [
        'id' => (int) $conexao->id,
        'db' => $conexao->db,
        'usuario' => $conexao->usuario,
        'pid' => getmypid(),
    ]);

    $payload = json_decode((string) file_get_contents($dir.'/payload-'.$papel.'.json'), true, 512, JSON_THROW_ON_ERROR);
    $segurarApos = (string) ($payload['segurar_apos'] ?? '');

    $sqlDisputado = static function (string $sql, string $marcador): bool {
        $sql = strtolower($sql);

        return $marcador !== '' && str_contains($sql, 'for update') && str_contains($sql, $marcador);
    };

    if ($papel === 'a' && $segurarApos !== '') {
        DB::listen(function ($consulta) use ($segurarApos, $sqlDisputado, $escrever, $esperar): void {
            static $segurou = false;
            if ($segurou || ! $sqlDisputado($consulta->sql, $segurarApos)) {
                return;
            }
            $segurou = true;
            $escrever('a-segurou.json', [
                'sql' => $consulta->sql,
                'em' => microtime(true),
            ]);
            $esperar('liberar.json', 25);
        });
    }

    if ($papel === 'b' && $segurarApos !== '') {
        DB::beforeExecuting(function (string $sql) use ($segurarApos, $sqlDisputado, $escrever): void {
            static $atingiu = false;
            if ($atingiu || ! $sqlDisputado($sql, $segurarApos)) {
                return;
            }
            $atingiu = true;
            $escrever('b-atingiu-for-update.json', [
                'sql' => $sql,
                'em' => microtime(true),
            ]);
        });
        DB::listen(function ($consulta) use ($segurarApos, $sqlDisputado, $escrever): void {
            static $passou = false;
            if ($passou || ! $sqlDisputado($consulta->sql, $segurarApos)) {
                return;
            }
            $passou = true;
            $escrever('b-passou-for-update.json', [
                'sql' => $consulta->sql,
                'em' => microtime(true),
            ]);
        });
    }

    $esperar('start-'.$papel.'.json', 25);
    $escrever($papel.'-iniciou.json', ['em' => microtime(true)]);

    $resultado = ['ok' => false];

    try {
        $acao = (string) $payload['acao'];
        if ($acao === 'confirmar') {
            $usuario = Usuario::query()->findOrFail($payload['usuario_id']);
            $pedido = app(ConfirmadorPedido::class)->confirmar(
                $usuario,
                (string) $payload['chave'],
                (string) $payload['assinatura'],
            );
            $resultado = [
                'ok' => true,
                'pedido_id' => $pedido->id,
                'codigo' => $pedido->codigo,
            ];
        } elseif ($acao === 'rebaixar') {
            $ator = Usuario::query()->findOrFail($payload['ator_id']);
            $alvo = Usuario::query()->findOrFail($payload['alvo_id']);
            Auth::login($ator);
            $request = Request::create('/admin/usuarios/'.$alvo->id.'/rebaixar', 'POST');
            $request->setUserResolver(fn () => $ator->fresh());
            app(UsuarioController::class)->rebaixar($request, $alvo);
            $resultado = ['ok' => true, 'rebaixado_id' => $alvo->id];
        } elseif ($acao === 'excluir') {
            $usuario = Usuario::query()->findOrFail($payload['usuario_id']);
            Auth::login($usuario);
            $sessao = app('session.store');
            $sessao->start();
            $request = Request::create('/perfil', 'DELETE', ['password' => (string) $payload['senha']]);
            $request->setLaravelSession($sessao);
            $request->setUserResolver(fn () => Auth::user());
            app()->instance('request', $request);
            $resposta = app(PerfilController::class)->excluir($request);
            $erros = $resposta->getSession()?->get('errors');
            $bloqueado = $erros !== null && $erros->getBag('exclusao_usuario')->isNotEmpty();
            $resultado = [
                'ok' => ! $bloqueado,
                'bloqueado' => $bloqueado,
                'status' => $resposta->getStatusCode(),
            ];
        } else {
            throw new RuntimeException('Ação desconhecida: '.$acao);
        }
    } catch (HttpException $excecao) {
        $resultado = [
            'ok' => false,
            'http' => $excecao->getStatusCode(),
            'mensagem' => $excecao->getMessage(),
        ];
    } catch (Throwable $excecao) {
        $resultado = [
            'ok' => false,
            'excecao' => $excecao::class,
            'mensagem' => $excecao->getMessage(),
        ];
    }

    $resultado['em'] = microtime(true);
    $escrever($papel.'-resultado.json', $resultado);
} catch (Throwable $excecao) {
    $escrever($papel.'-resultado.json', [
        'ok' => false,
        'excecao' => $excecao::class,
        'mensagem' => $excecao->getMessage(),
        'em' => microtime(true),
    ]);
    exit(1);
}
