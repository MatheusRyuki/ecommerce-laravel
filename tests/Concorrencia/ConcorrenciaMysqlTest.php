<?php

namespace Tests\Concorrencia;

use App\Cart\CarrinhoConta;
use App\Models\Cupom;
use App\Models\Endereco;
use App\Models\FaixaFrete;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\Usuario;
use App\Services\CalculadoraCheckout;
use App\Services\ConfirmadorPedido;
use App\Support\IsolamentoE2e;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcorrenciaMysqlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function (): void {
            IsolamentoE2e::garantir();
        });

        parent::setUp();
    }

    public function test_ultimo_estoque_nao_gera_dois_pedidos(): void
    {
        $produto = Produto::factory()->create(['preco' => '10.00', 'quantidade' => 1, 'cores' => ['Azul']]);
        FaixaFrete::query()->create(['cep_inicio' => '00000000', 'cep_fim' => '99999999', 'valor' => '0.00']);
        $a = Usuario::factory()->create();
        $b = Usuario::factory()->create();

        foreach ([$a, $b] as $user) {
            $this->criarEndereco($user);
            (new CarrinhoConta($user))->adicionar($produto->id, 'Azul', 1);
        }

        $this->confirmarPedido($a);

        $calculoB = $this->calculo($b);
        $this->assertFalse($calculoB['valido']);
        $this->assertSame(0, $produto->fresh()->quantidade);
        $this->assertSame(1, Pedido::query()->count());
    }

    public function test_cupom_de_uso_unico_nao_e_consumido_duas_vezes(): void
    {
        $produto = Produto::factory()->create(['preco' => '20.00', 'quantidade' => 4, 'cores' => ['Azul']]);
        FaixaFrete::query()->create(['cep_inicio' => '00000000', 'cep_fim' => '99999999', 'valor' => '0.00']);
        $cupom = Cupom::query()->create([
            'codigo' => 'UNICO',
            'tipo' => Cupom::TIPO_FIXO,
            'valor' => '1.00',
            'ativo' => true,
            'uso_unico' => true,
        ]);

        $primeiro = Usuario::factory()->create();
        $segundo = Usuario::factory()->create();

        foreach ([$primeiro, $segundo] as $user) {
            $this->criarEndereco($user);
            $carrinho = new CarrinhoConta($user);
            $carrinho->adicionar($produto->id, 'Azul', 1);
            $estado = $carrinho->estado();
            $estado->cupom_codigo = 'UNICO';
            $estado->save();
        }

        $this->confirmarPedido($primeiro);

        $calculoSegundo = $this->calculo($segundo);
        $this->assertFalse($calculoSegundo['valido']);
        $this->assertSame(1, Pedido::query()->count());
        $this->assertNotNull($cupom->fresh()->consumido_em);
    }

    private function criarEndereco(Usuario $user): void
    {
        Endereco::query()->create([
            'usuario_id' => $user->id,
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

    /**
     * @return array<string, mixed>
     */
    private function calculo(Usuario $usuario): array
    {
        $carrinho = new CarrinhoConta($usuario);

        return app(CalculadoraCheckout::class)->calcular($usuario, $carrinho->linhas(), $carrinho);
    }

    private function confirmarPedido(Usuario $usuario): void
    {
        $carrinho = new CarrinhoConta($usuario);
        $calculo = app(CalculadoraCheckout::class)->calcular($usuario, $carrinho->linhas(), $carrinho);
        $this->assertTrue($calculo['valido'], $calculo['motivo'] ?? 'cálculo inválido');

        $estado = $carrinho->estado();
        $chave = (string) Str::uuid();
        $estado->chave_checkout = $chave;
        $estado->save();

        app(ConfirmadorPedido::class)->confirmar($usuario, $chave, $calculo['assinatura']);
    }
}
