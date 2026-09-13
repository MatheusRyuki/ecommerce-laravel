<?php

namespace Tests\Feature;

use App\Cart\CarrinhoConta;
use App\Models\Cupom;
use App\Models\Endereco;
use App\Models\FaixaFrete;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\Usuario;
use App\Support\Cep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPedidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirma_pedido_baixa_estoque_e_e_idempotente(): void
    {
        $user = Usuario::factory()->create();
        $produto = Produto::factory()->create(['preco' => '10.00', 'quantidade' => 4, 'cores' => ['Azul'], 'sku' => 'CK-1']);
        FaixaFrete::query()->create(['cep_inicio' => '01000000', 'cep_fim' => '99999999', 'valor' => '12.50']);
        $cupom = Cupom::query()->create([
            'codigo' => 'DEZ',
            'tipo' => Cupom::TIPO_FIXO,
            'valor' => '2.00',
            'ativo' => true,
            'uso_unico' => true,
        ]);
        $endereco = Endereco::query()->create([
            'usuario_id' => $user->id,
            'destinatario' => 'Ana',
            'cep' => '01310100',
            'logradouro' => 'Rua A',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'padrao' => true,
        ]);

        $this->actingAs($user);
        $carrinho = new CarrinhoConta($user);
        $carrinho->adicionar($produto->id, 'Azul', 2);
        $estado = $carrinho->estado();
        $estado->cupom_codigo = 'DEZ';
        $estado->endereco_id = $endereco->id;
        $estado->save();

        $revisao = $this->get(route('checkout.revisar'))->assertOk();
        $chave = $carrinho->estado()->fresh()->chave_checkout;

        $this->post(route('checkout.confirmar'), ['chave' => $chave])->assertRedirect();
        $this->post(route('checkout.confirmar'), ['chave' => $chave]);

        $this->assertSame(1, Pedido::query()->count());
        $pedido = Pedido::query()->first();
        $this->assertSame('20.00', (string) $pedido->subtotal_produtos);
        $this->assertSame('2.00', (string) $pedido->desconto);
        $this->assertSame('12.50', (string) $pedido->frete);
        $this->assertSame('30.50', (string) $pedido->total);
        $this->assertSame(2, $produto->fresh()->quantidade);
        $this->assertNotNull($cupom->fresh()->consumido_em);
        $this->assertSame(0, $carrinho->quantidadeTotal());
        $this->assertSame(Cep::normalizar('01310-100'), $pedido->endereco_entrega['cep']);
    }

    public function test_cep_sem_cobertura_impede_checkout(): void
    {
        $user = Usuario::factory()->create();
        $produto = Produto::factory()->create(['quantidade' => 2, 'cores' => ['Azul']]);
        Endereco::query()->create([
            'usuario_id' => $user->id,
            'destinatario' => 'Ana',
            'cep' => '01310100',
            'logradouro' => 'Rua A',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'padrao' => true,
        ]);

        $this->actingAs($user);
        (new CarrinhoConta($user))->adicionar($produto->id, 'Azul', 1);

        $this->get(route('checkout.revisar'))->assertRedirect(route('carrinho'));
    }

    public function test_ultimo_item_nao_e_vendido_duas_vezes_em_sequencia(): void
    {
        $produto = Produto::factory()->create(['preco' => '10.00', 'quantidade' => 1, 'cores' => ['Azul']]);
        FaixaFrete::query()->create(['cep_inicio' => '00000000', 'cep_fim' => '99999999', 'valor' => '0.00']);
        $a = Usuario::factory()->create();
        $b = Usuario::factory()->create();
        foreach ([$a, $b] as $user) {
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
            $this->actingAs($user);
            (new CarrinhoConta($user))->adicionar($produto->id, 'Azul', 1);
        }

        $this->actingAs($a);
        $this->get(route('checkout.revisar'))->assertOk();
        $chaveA = (new CarrinhoConta($a))->estado()->chave_checkout;
        $this->post(route('checkout.confirmar'), ['chave' => $chaveA])->assertRedirect();

        $this->actingAs($b);
        $this->get(route('checkout.revisar'))->assertRedirect(route('carrinho'));
        $this->assertSame(0, $produto->fresh()->quantidade);
        $this->assertSame(1, Pedido::query()->count());
    }
}
