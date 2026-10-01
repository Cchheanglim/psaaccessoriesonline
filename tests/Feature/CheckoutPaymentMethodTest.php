<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutPaymentMethodTest extends TestCase
{
    public function test_new_payment_methods_pass_checkout_validation(): void
    {
        foreach (['acleda_khqr', 'visa_card'] as $paymentMethod) {
            $this->post(route('checkout.store'), [
                'customer_name' => 'Test Buyer',
                'customer_phone' => '+855 12 345 678',
                'delivery_address' => 'Phnom Penh',
                'payment_method' => $paymentMethod,
            ])->assertRedirect(route('products.index'));
        }
    }

    public function test_unknown_payment_methods_are_rejected(): void
    {
        $this->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Buyer',
                'customer_phone' => '+855 12 345 678',
                'delivery_address' => 'Phnom Penh',
                'payment_method' => 'unknown',
            ])
            ->assertSessionHasErrors('payment_method');
    }
}