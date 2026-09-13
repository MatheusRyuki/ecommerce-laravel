<?php

namespace Tests\Feature;

use Tests\TestCase;

class RotasAuxiliaresE2eAusentesTest extends TestCase
{
    public function test_diagnostico_e_armazenamento_e2e_nao_existem_fora_do_ambiente_e2e(): void
    {
        $this->get('/_e2e/diagnostico')->assertNotFound();
        $this->get('/armazenamento-e2e/products/x.png')->assertNotFound();
    }
}
