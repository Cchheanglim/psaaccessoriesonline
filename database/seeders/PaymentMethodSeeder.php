<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Makes sure every payment option on checkout.html has a row, so admins can turn it on or off.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            'bakong_khqr' => ['name' => 'Bakong Universal KHQR', 'type' => 'khqr', 'account_number' => 'psaonline@aclb', 'description' => 'Scan with any Cambodian banking app'],
            'aba_pay' => ['name' => 'ABA Mobile Pay', 'type' => 'bank', 'description' => 'Instant redirect to the ABA Mobile app'],
            'acleda_khqr' => ['name' => 'ACLEDA Mobile KHQR', 'type' => 'khqr', 'description' => 'ACLEDA app deep link with KHQR fallback'],
            'visa_card' => ['name' => 'Visa / Mastercard', 'type' => 'other', 'description' => 'Card payment (demo, no real charge)'],
            'cod' => ['name' => 'Cash on Delivery', 'type' => 'cod', 'description' => 'Pay the courier in cash (Phnom Penh only)'],
        ];

        foreach ($methods as $code => $attributes) {
            $method = PaymentMethod::firstOrNew(['code' => $code]);
            if (! $method->exists) {
                $method->fill($attributes + ['is_active' => true]);
            } else {
                $method->type = $attributes['type'];
            }
            $method->save();
        }
    }
}
