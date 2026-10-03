<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class CheckoutPaymentMethodTest extends TestCase
{
    // Checkout needs a signed-in account; an unsaved user is enough because these
    // requests stop before anything is written (the bag is empty).
    private function buyer(): User
    {
        return (new User(['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'role' => 'buyer']))->forceFill(['id' => 1]);
    }

    public function test_new_payment_methods_pass_checkout_validation(): void
    {
        foreach (['acleda_khqr', 'visa_card'] as $paymentMethod) {
            $this->actingAs($this->buyer())->post(route('checkout.store'), [
                'customer_name' => 'Test Buyer',
                'customer_phone' => '+855 12 345 678',
                'delivery_address' => 'Phnom Penh',
                'payment_method' => $paymentMethod,
            ])->assertRedirect(route('products.index'));
        }
    }

    public function test_unknown_payment_methods_are_rejected(): void
    {
        $this->actingAs($this->buyer())
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Buyer',
                'customer_phone' => '+855 12 345 678',
                'delivery_address' => 'Phnom Penh',
                'payment_method' => 'unknown',
            ])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->post(route('checkout.store'), [])->assertRedirect('/login.html');
    }
}
