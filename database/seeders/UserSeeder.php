<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        // A throwaway buyer for clicking through checkout locally. Never
        // created on a live database.
        if (! app()->isProduction()) {
            User::firstOrCreate(
                ['email' => 'buyer@example.test'],
                [
                    'name' => 'Demo Buyer',
                    'password' => Hash::make('password123'),
                    'role' => 'buyer',
                ]
            );
        }

        $this->seedPaymentMethods();
    }

    /**
     * The admin password comes from ADMIN_SEED_PASSWORD so it never lives in
     * the repository. In production the seeder refuses to run without one.
     */
    protected function seedAdmin(): void
    {
        $email = config('app.admin_seed.email');
        $password = config('app.admin_seed.password');

        if (! $password) {
            if (app()->isProduction()) {
                throw new RuntimeException('Set ADMIN_SEED_PASSWORD before seeding a production database.');
            }

            $password = 'admin-local-only-1';
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Store Administrator',
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );
    }

    protected function seedPaymentMethods(): void
    {
        PaymentMethod::firstOrCreate(
            ['code' => 'bakong_khqr'],
            [
                'name' => 'Bakong Universal KHQR',
                'account_name' => config('services.bakong.merchant_name'),
                'account_number' => config('services.bakong.merchant_id') ?? '',
                'is_active' => true,
                'description' => 'Scan via ABA, ACLEDA, Canadia, Wing, or any Cambodian Mobile Bank.',
                'icon' => 'khqr',
            ]
        );

        PaymentMethod::firstOrCreate(
            ['code' => 'aba_pay'],
            [
                'name' => 'ABA PAY (Instant App Redirect)',
                'account_name' => 'PSA ONLINE ACCESSORIES',
                'account_number' => '',
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
