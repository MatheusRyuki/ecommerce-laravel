<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LojaPaginasTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_inicial_e_exibida(): void
    {
        $response = $this->get(route('inicio'));

        $response->assertOk();
        $response->assertSee('Ainda não há produtos na loja.');
    }

    public function test_url_antiga_de_detalhes_redireciona_para_inicio(): void
    {
        $this->get('/product-details')->assertRedirect(route('inicio'));
    }

    public function test_pagina_do_carrinho_e_exibida(): void
    {
        $response = $this->get(route('carrinho'));

        $response->assertOk();
        $response->assertSee('Seu carrinho está vazio.');
        $response->assertSee('Total dos produtos');
        $response->assertSee('R$ 0,00');
        $response->assertDontSee('Sub total');
        $response->assertDontSee('$ 360.00');
    }
}
