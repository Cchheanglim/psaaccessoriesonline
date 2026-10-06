<?php

namespace Tests\Unit;

use App\Models\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusStepsTest extends TestCase
{
    public function test_orders_only_move_forward_through_real_steps(): void
    {
        $this->assertTrue(OrderStatus::canMove('pending_payment', 'processing'));
        $this->assertTrue(OrderStatus::canMove('processing', 'out_for_delivery'));
        $this->assertTrue(OrderStatus::canMove('out_for_delivery', 'delivered'));
        $this->assertTrue(OrderStatus::canMove('processing', 'delivered')); // handed over in the shop

        $this->assertFalse(OrderStatus::canMove('pending_payment', 'delivered'));
        $this->assertFalse(OrderStatus::canMove('delivered', 'processing'));
        $this->assertFalse(OrderStatus::canMove('delivered', 'cancelled'));
        $this->assertFalse(OrderStatus::canMove('cancelled', 'processing'));
    }
}
