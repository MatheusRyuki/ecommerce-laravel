<?php

namespace Tests\Unit;

use App\Support\Dinheiro;
use PHPUnit\Framework\TestCase;

class DinheiroTest extends TestCase
{
    public function test_multiplica_e_soma_sem_float(): void
    {
        $this->assertSame('149.70', Dinheiro::multiplicar('49.90', 3));
        $this->assertSame('199.60', Dinheiro::somar('149.70', '49.90'));
        $this->assertSame('199999999.98', Dinheiro::multiplicar('99999999.99', 2));
        $this->assertSame('R$ 199.999.999,98', Dinheiro::formatarBrl('199999999.98'));
        $this->assertSame('0.03', Dinheiro::multiplicar('0.01', 3));
    }
}
