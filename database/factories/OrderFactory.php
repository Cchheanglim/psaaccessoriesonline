<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'PSA-'.Str::upper(Str::random(6)),
            'user_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => '+855 12 345 678',
            'delivery_address' => 'Street 1, Phnom Penh',
            'subtotal_usd' => 10.00,
            'subtotal_khr' => 41000,
            'delivery_fee_usd' => 1.50,
            'delivery_fee_khr' => 6150,
            'total_usd' => 11.50,
            'total_khr' => 47150,
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending_slip',
            'order_status' => 'pending_payment',
        ];
    }
}
