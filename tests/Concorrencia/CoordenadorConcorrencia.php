<?php

namespace Tests\Concorrencia;

use App\Support\IsolamentoE2e;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CoordenadorConcorrencia
{
    /** @var list<resource> */
    private array $processos = [];

    public function __construct(private string $dir)
    {
        if (! is_dir($this->dir) && ! mkdir($this->dir, 0777, true) && ! is_dir($this->dir)) {
            throw new RuntimeException('Não foi possível criar '.$this->dir);
        }
    }

    /**
     * @param  array<string, mixed>  $payloadA
     * @param  array<string, mixed>  $payloadB
     * @return array{pai: array<string, mixed>, a: array<string, mixed>, b: array<string, mixed>, evidencia: array<string, mixed>, resultado_a: array<string, mixed>, resultado_b: array<string, mixed>}
     */
    public function executar(array $payloadA, array $payloadB): array
    {
        $this->confirmarConexaoPai();
        if (($payloadA['segurar_apos'] ?? '') === '') {
            throw new RuntimeException('O payload de A precisa de segurar_apos para marcar o recurso disputado.');
        }
        if (($payloadB['segurar_apos'] ?? '') === '') {
            $payloadB['segurar_apos'] = $payloadA['segurar_apos'];
        }
        file_put_contents($this->dir.'/payload-a.json', json_encode($payloadA, JSON_THROW_ON_ERROR));
        file_put_contents($this->dir.'/payload-b.json', json_encode($payloadB, JSON_THROW_ON_ERROR));

        $this->iniciarFilho('a');
        $this->iniciarFilho('b');

        $conexaoA = $this->esperarJson('a-conexao.json', 20);
        $conexaoB = $this->esperarJson('b-conexao.json', 20);
        $pai = $this->conexaoAtual();

        if ((int) $conexaoA['id'] === (int) $conexaoB['id'] || (int) $conexaoA['id'] === (int) $pai['id'] || (int) $conexaoB['id'] === (int) $pai['id']) {
            throw new RuntimeException('As conexões MySQL não são independentes: pai='.$pai['id'].' a='.$conexaoA['id'].' b='.$conexaoB['id']);
        }

        $this->sinalizar('start-a.json');
        $segurou = $this->esperarJson('a-segurou.json', 20);
        $this->sinalizar('start-b.json');
        $iniciouB = $this->esperarJson('b-iniciou.json', 20);
        $atingiuB = $this->esperarJson('b-atingiu-for-update.json', 20);

        if (is_file($this->dir.'/liberar.json')) {
            throw new RuntimeException('liberar.json existia antes de B atingir o FOR UPDATE.');
        }

        if (is_file($this->dir.'/b-passou-for-update.json')) {
            throw new RuntimeException('B concluiu o FOR UPDATE do recurso disputado enquanto A ainda deveria retê-lo; não houve espera pelo lock.');
        }

        if (is_file($this->dir.'/b-resultado.json')) {
            throw new RuntimeException('B concluiu a ação antes de A liberar a transação.');
        }

        $liberouEm = microtime(true);
        $this->sinalizar('liberar.json', ['em' => $liberouEm]);

        $resultadoA = $this->esperarJson('a-resultado.json', 20);
        $resultadoB = $this->esperarJson('b-resultado.json', 20);

        if (($atingiuB['em'] ?? 0) >= $liberouEm) {
            throw new RuntimeException('B só despachou o FOR UPDATE depois de liberar.json.');
        }

        $passouB = is_file($this->dir.'/b-passou-for-update.json')
            ? json_decode((string) file_get_contents($this->dir.'/b-passou-for-update.json'), true, 512, JSON_THROW_ON_ERROR)
            : null;

        if (is_array($passouB) && ($passouB['em'] ?? 0) < $liberouEm) {
            throw new RuntimeException('B obteve o FOR UPDATE antes da liberação da transação de A.');
        }

        if (($resultadoB['em'] ?? 0) < $liberouEm) {
            throw new RuntimeException('O segundo processo terminou antes da liberação da primeira transação.');
        }

        $this->encerrarFilhos();

        return [
            'pai' => $pai,
            'a' => $conexaoA,
            'b' => $conexaoB,
            'evidencia' => [
                'sql_lock_a' => $segurou['sql'] ?? null,
                'a_segurou_em' => $segurou['em'] ?? null,
                'b_iniciou_em' => $iniciouB['em'] ?? null,
                'sql_for_update_b' => $atingiuB['sql'] ?? null,
                'b_atingiu_for_update_em' => $atingiuB['em'] ?? null,
                'b_passou_for_update_em' => is_array($passouB) ? ($passouB['em'] ?? null) : null,
                'liberou_em' => $liberouEm,
                'b_terminou_em' => $resultadoB['em'] ?? null,
                'b_bloqueado_no_for_update' => ($atingiuB['em'] ?? 0) < $liberouEm && $passouB === null
                    || (is_array($passouB) && ($passouB['em'] ?? 0) >= $liberouEm && ($atingiuB['em'] ?? 0) < $liberouEm),
            ],
            'resultado_a' => $resultadoA,
            'resultado_b' => $resultadoB,
        ];
    }

    public function encerrarFilhos(): void
    {
        foreach ($this->processos as $processo) {
            $status = proc_get_status($processo);
            if (($status['running'] ?? false) === true) {
                proc_terminate($processo);
            }
            proc_close($processo);
        }
        $this->processos = [];
    }

    public function limpar(): void
    {
        $this->encerrarFilhos();
        if (! is_dir($this->dir)) {
            return;
        }
        foreach (scandir($this->dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            @unlink($this->dir.'/'.$item);
        }
        @rmdir($this->dir);
    }

    /**
     * @return array{id: int, db: string, usuario: string}
     */
    public function conexaoAtual(): array
    {
        IsolamentoE2e::garantir();
        $conexao = DB::selectOne('select database() as db, current_user() as usuario, connection_id() as id');

        if (($conexao->db ?? '') !== IsolamentoE2e::BANCO) {
            throw new RuntimeException('Processo principal conectado a '.($conexao->db ?? 'null'));
        }

        if (str_contains((string) $conexao->usuario, 'sail') || str_starts_with((string) $conexao->usuario, 'root@')) {
            throw new RuntimeException('Processo principal usando usuário proibido: '.$conexao->usuario);
        }

        return [
            'id' => (int) $conexao->id,
            'db' => (string) $conexao->db,
            'usuario' => (string) $conexao->usuario,
        ];
    }

    private function confirmarConexaoPai(): void
    {
        $this->conexaoAtual();
    }

    private function iniciarFilho(string $papel): void
    {
        $cmd = [PHP_BINARY, base_path('tests/Concorrencia/trabalhador.php')];
        $descritores = [
            0 => ['pipe', 'r'],
            1 => ['file', $this->dir.'/'.$papel.'.out', 'w'],
            2 => ['file', $this->dir.'/'.$papel.'.err', 'w'],
        ];
        $env = getenv();
        if (! is_array($env)) {
            $env = $_ENV;
        }
        $env['APP_ENV'] = 'e2e';
        $env['CONCORRENCIA_DIR'] = $this->dir;
        $env['CONCORRENCIA_PAPEL'] = $papel;

        $processo = proc_open($cmd, $descritores, $pipes, base_path(), $env);
        if (! is_resource($processo)) {
            throw new RuntimeException('Não foi possível iniciar o processo '.$papel);
        }
        fclose($pipes[0]);
        $this->processos[] = $processo;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function sinalizar(string $nome, array $dados = []): void
    {
        file_put_contents($this->dir.'/'.$nome, json_encode($dados + ['em' => microtime(true)], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function esperarJson(string $nome, float $limite): array
    {
        $caminho = $this->dir.'/'.$nome;
        $inicio = microtime(true);
        while (! is_file($caminho)) {
            $papelDono = str_starts_with($nome, 'a-') || str_starts_with($nome, 'start-a') || $nome === 'a-segurou.json'
                ? 'a'
                : (str_starts_with($nome, 'b-') ? 'b' : null);
            if ($papelDono !== null && $this->processos !== []) {
                $indice = $papelDono === 'a' ? 0 : 1;
                if (isset($this->processos[$indice])) {
                    $status = proc_get_status($this->processos[$indice]);
                    if (($status['running'] ?? true) === false && ! is_file($caminho)) {
                        $erro = is_file($this->dir.'/'.$papelDono.'.err') ? (string) file_get_contents($this->dir.'/'.$papelDono.'.err') : '';
                        throw new RuntimeException('O processo '.$papelDono.' encerrou antes de '.$nome.'. '.$erro);
                    }
                }
            }
            if ((microtime(true) - $inicio) > $limite) {
                throw new RuntimeException('Timeout à espera de '.$nome);
            }
            usleep(20_000);
        }

        /** @var array<string, mixed> $dados */
        $dados = json_decode((string) file_get_contents($caminho), true, 512, JSON_THROW_ON_ERROR);

        return $dados;
    }
}
