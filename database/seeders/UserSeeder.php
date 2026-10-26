<?php

namespace Database\Seeders;

use App\Models\User;
// Tạo tài khoản người dùng ban đầu.

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'full_name' => 'Quản trị viên',
                'phone' => '0900000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

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

        User::factory()
            ->count(8)
            ->create()
            ->each(fn (User $u) => $u->assignRole('customer'));
    }
}
