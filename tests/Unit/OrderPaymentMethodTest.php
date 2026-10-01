<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderPaymentMethodTest extends TestCase
{
    public function test_payment_method_is_mass_assignable(): void
    {
        $order = new Order(['payment_method' => 'acleda_khqr']);

        $this->assertSame('acleda_khqr', $order->payment_method);
    }
}