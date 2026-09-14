<?php

namespace Tests\Concorrencia;

use App\Support\IsolamentoE2e;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class SondaLockNowait
{
    public const MYSQL_NOWAIT = 3572;

    /**
     * @param  list<mixed>|array<string, mixed>  $bindings
     * @return array{comprovado: bool, mysql: ?int, sqlstate: ?string, mensagem: ?string, sql: string, conexao: int, db: string, usuario: string}
     */
    public static function executar(string $sql, array $bindings): array
    {
        $sqlSonda = self::comNowait($sql);
        $nome = 'sonda_lock_'.bin2hex(random_bytes(4));
        $config = config('database.connections.mysql');
        if (! is_array($config)) {
            throw new RuntimeException('Configuração mysql ausente para a sondagem.');
        }
        config(["database.connections.{$nome}" => $config]);

        $conexao = DB::connection($nome);
        $info = $conexao->selectOne('select database() as db, current_user() as usuario, connection_id() as id');
        if (($info->db ?? '') !== IsolamentoE2e::BANCO) {
            throw new RuntimeException('Sondagem conectada a '.($info->db ?? 'null'));
        }
        if (str_contains((string) $info->usuario, 'sail') || str_starts_with((string) $info->usuario, 'root@')) {
            throw new RuntimeException('Sondagem usando usuário proibido: '.$info->usuario);
        }

        $pdo = $conexao->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $mysql = null;
        $sqlstate = null;
        $mensagem = null;
        $comprovado = false;

        $iniciou = false;
        try {
            $iniciou = $pdo->beginTransaction();
            $stmt = $pdo->prepare($sqlSonda);
            $stmt->execute(array_values($bindings));
        } catch (PDOException $excecao) {
            $sqlstate = (string) ($excecao->errorInfo[0] ?? $excecao->getCode());
            $mysql = isset($excecao->errorInfo[1]) ? (int) $excecao->errorInfo[1] : null;
            $mensagem = $excecao->errorInfo[2] ?? $excecao->getMessage();
            $comprovado = $mysql === self::MYSQL_NOWAIT;
        } catch (Throwable $excecao) {
            $mensagem = $excecao->getMessage();
        } finally {
            if ($iniciou && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            DB::disconnect($nome);
            DB::purge($nome);
        }

        return [
            'comprovado' => $comprovado,
            'mysql' => $mysql,
            'sqlstate' => $sqlstate,
            'mensagem' => $mensagem,
            'sql' => $sqlSonda,
            'conexao' => (int) $info->id,
            'db' => (string) $info->db,
            'usuario' => (string) $info->usuario,
        ];
    }

    /**
     * @param  array<string, mixed>  $sonda
     */
    public static function exigirErro3572(array $sonda): void
    {
        if (($sonda['comprovado'] ?? false) !== true || (int) ($sonda['mysql'] ?? 0) !== self::MYSQL_NOWAIT) {
            throw new RuntimeException(
                'A sondagem FOR UPDATE NOWAIT não devolveu o MySQL 3572 (código '.($sonda['mysql'] ?? 'nulo').'). Sucesso ou outro erro não comprovam o conflito.',
            );
        }
    }

    public static function comNowait(string $sql): string
    {
        if (preg_match('/for update\s+nowait\b/i', $sql) === 1) {
            return $sql;
        }

        $alterado = preg_replace('/for update\s*$/i', 'for update nowait', trim($sql));
        if (! is_string($alterado) || ! str_contains(strtolower($alterado), 'nowait')) {
            throw new RuntimeException('Não foi possível acrescentar NOWAIT à consulta de sondagem.');
        }

        return $alterado;
    }
}
