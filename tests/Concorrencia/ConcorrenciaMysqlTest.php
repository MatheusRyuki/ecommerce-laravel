<?php

namespace Tests\Concorrencia;

use App\Cart\CarrinhoConta;
use App\Models\Cupom;
use App\Models\Endereco;
use App\Models\FaixaFrete;
use App\Models\MovimentacaoEstoque;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\Usuario;
use App\Services\CalculadoraCheckout;
use App\Support\IsolamentoE2e;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcorrenciaMysqlTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected $connectionsToTransact = [];

    private ?CoordenadorConcorrencia $coordenador = null;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function (): void {
            IsolamentoE2e::garantir();
        });

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->coordenador?->limpar();
        $this->coordenador = null;
        parent::tearDown();
    }

    protected function refreshTestDatabase()
    {
        IsolamentoE2e::garantir();
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_ultimo_estoque_nao_gera_dois_pedidos(): void
    {
        $produto = Produto::factory()->create(['preco' => '10.00', 'quantidade' => 1, 'cores' => ['Azul']]);
        $this->faixaFrete();
        $a = Usuario::factory()->create();
        $b = Usuario::factory()->create();
        $prepA = $this->prepararCheckout($a, $produto, 1);
        $prepB = $this->prepararCheckout($b, $produto, 1);
        $this->assertSame(1, $produto->fresh()->quantidade);

        $corrida = $this->coordenar(
            [
                'acao' => 'confirmar',
                'segurar_apos' => 'produtos',
                'usuario_id' => $a->id,
                'chave' => $prepA['chave'],
                'assinatura' => $prepA['assinatura'],
            ],
            [
                'acao' => 'confirmar',
                'usuario_id' => $b->id,
                'chave' => $prepB['chave'],
                'assinatura' => $prepB['assinatura'],
            ],
        );

        $this->assertTrue($corrida['resultado_a']['ok'] ?? false, json_encode($corrida['resultado_a']));
        $this->assertFalse($corrida['resultado_b']['ok'] ?? true, json_encode($corrida['resultado_b']));
        $this->assertSame(1, Pedido::query()->count());
        $this->assertSame(0, $produto->fresh()->quantidade);
        $this->assertSame(1, MovimentacaoEstoque::query()->where('tipo', MovimentacaoEstoque::TIPO_PEDIDO)->count());
        $this->assertSame(1, (new CarrinhoConta($b->fresh()))->quantidadeTotal());
        $this->assertNull(Pedido::query()->where('usuario_id', $b->id)->first());
        $this->assertSobreposicao($corrida);
    }

    public function test_cupom_de_uso_unico_nao_e_consumido_duas_vezes(): void
    {
        $produtoA = Produto::factory()->create(['preco' => '20.00', 'quantidade' => 5, 'cores' => ['Azul']]);
        $produtoB = Produto::factory()->create(['preco' => '22.00', 'quantidade' => 5, 'cores' => ['Verde']]);
        $this->faixaFrete();
        $cupom = Cupom::query()->create([
            'codigo' => 'UNICO',
            'tipo' => Cupom::TIPO_FIXO,
            'valor' => '1.00',
            'ativo' => true,
            'uso_unico' => true,
        ]);
        $primeiro = Usuario::factory()->create();
        $segundo = Usuario::factory()->create();
        $prepA = $this->prepararCheckout($primeiro, $produtoA, 1, 'UNICO');
        $prepB = $this->prepararCheckout($segundo, $produtoB, 1, 'UNICO');

        $corrida = $this->coordenar(
            [
                'acao' => 'confirmar',
                'segurar_apos' => 'cupons',
                'usuario_id' => $primeiro->id,
                'chave' => $prepA['chave'],
                'assinatura' => $prepA['assinatura'],
            ],
            [
                'acao' => 'confirmar',
                'usuario_id' => $segundo->id,
                'chave' => $prepB['chave'],
                'assinatura' => $prepB['assinatura'],
            ],
        );

        $this->assertTrue($corrida['resultado_a']['ok'] ?? false, json_encode($corrida['resultado_a']));
        $this->assertFalse($corrida['resultado_b']['ok'] ?? true, json_encode($corrida['resultado_b']));
        $this->assertSame(1, Pedido::query()->count());
        $this->assertNotNull($cupom->fresh()->consumido_em);
        $this->assertSame(1, Pedido::query()->whereNotNull('cupom_codigo')->count());
        $this->assertSame(5, $produtoB->fresh()->quantidade);
        $this->assertSame(1, (new CarrinhoConta($segundo->fresh()))->quantidadeTotal());
        $this->assertSobreposicao($corrida);
    }

    public function test_confirmacao_duplicada_identifica_o_mesmo_pedido(): void
    {
        $produto = Produto::factory()->create(['preco' => '15.00', 'quantidade' => 3, 'cores' => ['Azul']]);
        $this->faixaFrete();
        $cupom = Cupom::query()->create([
            'codigo' => 'IDEMP',
            'tipo' => Cupom::TIPO_FIXO,
            'valor' => '1.00',
            'ativo' => true,
            'uso_unico' => true,
        ]);
        $usuario = Usuario::factory()->create();
        $prep = $this->prepararCheckout($usuario, $produto, 1, 'IDEMP');

        $corrida = $this->coordenar(
            [
                'acao' => 'confirmar',
                'segurar_apos' => 'pedidos',
                'usuario_id' => $usuario->id,
                'chave' => $prep['chave'],
                'assinatura' => $prep['assinatura'],
            ],
            [
                'acao' => 'confirmar',
                'usuario_id' => $usuario->id,
                'chave' => $prep['chave'],
                'assinatura' => $prep['assinatura'],
            ],
        );

        $this->assertTrue($corrida['resultado_a']['ok'] ?? false, json_encode($corrida['resultado_a']));
        $this->assertTrue($corrida['resultado_b']['ok'] ?? false, json_encode($corrida['resultado_b']));
        $this->assertSame($corrida['resultado_a']['pedido_id'], $corrida['resultado_b']['pedido_id']);
        $this->assertSame(1, Pedido::query()->count());
        $this->assertSame(2, $produto->fresh()->quantidade);
        $this->assertSame(1, MovimentacaoEstoque::query()->where('tipo', MovimentacaoEstoque::TIPO_PEDIDO)->count());
        $this->assertNotNull($cupom->fresh()->consumido_em);
        $this->assertSobreposicao($corrida);
    }

    public function test_rebaixar_em_paralelo_preserva_um_administrador(): void
    {
        $alfa = Usuario::factory()->admin()->create(['name' => 'Admin Alfa']);
        $beta = Usuario::factory()->admin()->create(['name' => 'Admin Beta']);
        $this->assertSame(2, Usuario::query()->where('administrador', true)->count());

        $corrida = $this->coordenar(
            [
                'acao' => 'rebaixar',
                'segurar_apos' => 'administrador',
                'ator_id' => $alfa->id,
                'alvo_id' => $beta->id,
            ],
            [
                'acao' => 'rebaixar',
                'ator_id' => $beta->id,
                'alvo_id' => $alfa->id,
            ],
        );

        $admins = Usuario::query()->where('administrador', true)->count();
        $this->assertGreaterThanOrEqual(1, $admins);
        $this->assertFalse(($corrida['resultado_a']['ok'] ?? false) && ($corrida['resultado_b']['ok'] ?? false), json_encode($corrida));
        $this->assertSobreposicao($corrida);
    }

    public function test_exclusao_simultanea_de_contas_preserva_um_administrador(): void
    {
        $alfa = Usuario::factory()->admin()->create();
        $beta = Usuario::factory()->admin()->create();

        $corrida = $this->coordenar(
            [
                'acao' => 'excluir',
                'segurar_apos' => 'administrador',
                'usuario_id' => $alfa->id,
                'senha' => 'password',
            ],
            [
                'acao' => 'excluir',
                'usuario_id' => $beta->id,
                'senha' => 'password',
            ],
        );

        $this->assertGreaterThanOrEqual(1, Usuario::query()->where('administrador', true)->count());
        $this->assertFalse(($corrida['resultado_a']['ok'] ?? false) && ($corrida['resultado_b']['ok'] ?? false), json_encode($corrida));
        $this->assertSobreposicao($corrida);
    }

    /**
     * @param  array<string, mixed>  $payloadA
     * @param  array<string, mixed>  $payloadB
     * @return array<string, mixed>
     */
    private function coordenar(array $payloadA, array $payloadB): array
    {
        $this->coordenador?->limpar();
        $this->coordenador = new CoordenadorConcorrencia(storage_path('e2e/concorrencia/'.Str::uuid()));

        return $this->coordenador->executar($payloadA, $payloadB);
    }

    /**
     * @param  array<string, mixed>  $corrida
     */
    private function assertSobreposicao(array $corrida): void
    {
        $this->assertNotSame($corrida['pai']['id'], $corrida['a']['id']);
        $this->assertNotSame($corrida['pai']['id'], $corrida['b']['id']);
        $this->assertNotSame($corrida['a']['id'], $corrida['b']['id']);
        $this->assertTrue($corrida['evidencia']['b_bloqueado_ate_liberar']);
        $this->assertNotEmpty($corrida['evidencia']['sql_lock_a']);
        $this->assertGreaterThan($corrida['evidencia']['liberou_em'], $corrida['evidencia']['b_terminou_em']);
    }

    /**
     * @return array{chave: string, assinatura: string}
     */
    private function prepararCheckout(Usuario $usuario, Produto $produto, int $quantidade, ?string $cupom = null): array
    {
        $this->criarEndereco($usuario);
        $carrinho = new CarrinhoConta($usuario);
        $carrinho->adicionar($produto->id, $produto->coresExibidas()[0], $quantidade);
        $estado = $carrinho->estado();
        $estado->cupom_codigo = $cupom;
        $chave = (string) Str::uuid();
        $estado->chave_checkout = $chave;
        $estado->save();

        $calculo = app(CalculadoraCheckout::class)->calcular($usuario, $carrinho->linhas(), $carrinho);
        $this->assertTrue($calculo['valido'], $calculo['motivo'] ?? 'cálculo inválido');
        $this->assertNotNull(Usuario::query()->find($usuario->id));
        $this->assertGreaterThan(0, (new CarrinhoConta($usuario))->quantidadeTotal());

        return [
            'chave' => $chave,
            'assinatura' => (string) $calculo['assinatura'],
        ];
    }

    private function criarEndereco(Usuario $usuario): void
    {
        Endereco::query()->create([
            'usuario_id' => $usuario->id,
            'destinatario' => 'X',
            'cep' => '01310100',
            'logradouro' => 'Rua',
            'numero' => '1',
            'bairro' => 'B',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'padrao' => true,
        ]);
    }

    private function faixaFrete(): void
    {
        FaixaFrete::query()->create([
            'cep_inicio' => '00000000',
            'cep_fim' => '99999999',
            'valor' => '0.00',
        ]);
    }
}
