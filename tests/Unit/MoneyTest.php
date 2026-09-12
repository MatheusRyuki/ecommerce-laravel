<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_multiplies_and_adds_without_float(): void
    {
        $this->assertSame('149.70', Money::multiply('49.90', 3));
        $this->assertSame('199.60', Money::add('149.70', '49.90'));
        $this->assertSame('199999999.98', Money::multiply('99999999.99', 2));
        $this->assertSame('R$ 199.999.999,98', Money::formatBrl('199999999.98'));
        $this->assertSame('0.03', Money::multiply('0.01', 3));
    }
}
