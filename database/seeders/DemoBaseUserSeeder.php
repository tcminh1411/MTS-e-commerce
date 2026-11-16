<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Mục đích: tạo staff và 8 customer factory cho dữ liệu demo.
// Phụ thuộc: RolePermissionSeeder.
class DemoBaseUserSeeder extends Seeder
{
    private const CUSTOMER_COUNT = 8;

    public function run(): void
    {
        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'full_name' => 'Nhân viên',
                'phone' => '0900000002',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $staff->assignRole('staff');

        for ($customerNumber = 1; $customerNumber <= self::CUSTOMER_COUNT; $customerNumber++) {
            $email = "factory-customer{$customerNumber}@demo.local";
            $customerData = User::factory()->make([
                'email' => $email,
            ])->getAttributes();

            $customer = User::firstOrCreate(
                ['email' => $email],
                $customerData
            );
            $customer->assignRole('customer');
        }
    }
}
