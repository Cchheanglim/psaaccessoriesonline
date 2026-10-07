<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Account
        User::firstOrCreate(
            ['email' => 'admin@psaonline.store'],
            [
                'name' => 'Store Administrator',
                'phone' => '+855 12 889 900',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
            ]
        );

        // Demo Buyer Account (with a saved home address)
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@gmail.com'],
            [
                'name' => 'Sophea Chhum',
                'phone' => '+855 96 554 1234',
                'password' => Hash::make('password123'),
                'role' => 'buyer',
            ]
        );
        if (! $buyer->addresses()->exists()) {
            $buyer->asCustomer()->addresses()->create([
                'label' => 'Home', 'recipient_name' => $buyer->name, 'phone' => $buyer->phone,
                'address_line' => 'Toul Kork, St 315, House #14, Phnom Penh', 'is_default' => true,
            ]);
        }

        // Default Payment Gateways
        PaymentMethod::firstOrCreate(
            ['code' => 'bakong_khqr'],
            [
                'name' => 'Bakong Universal KHQR',
                'account_name' => 'PSA ONLINE ACCESSORIES',
                'account_number' => 'psaonline@aclb',
                'is_active' => true,
                'description' => 'Scan via ABA, ACLEDA, Canadia, Wing, or any Cambodian Mobile Bank.',
                'icon' => 'khqr',
            ]
        );

        PaymentMethod::firstOrCreate(
            ['code' => 'aba_pay'],
            [
                'name' => 'ABA PAY (Instant App Redirect)',
                'account_name' => 'PSA ONLINE STORE',
                'account_number' => '001 234 567',
                'is_active' => true,
                'description' => 'Fast checkout for ABA Mobile app users.',
                'icon' => 'aba',
            ]
        );

        PaymentMethod::firstOrCreate(
            ['code' => 'cod'],
            [
                'name' => 'Cash On Delivery (Phnom Penh Only)',
                'account_name' => 'Courier Handover',
                'account_number' => 'N/A',
                'is_active' => true,
                'description' => 'Pay cash to the courier upon arrival at your doorstep.',
                'icon' => 'cash',
            ]
        );
    }
}
