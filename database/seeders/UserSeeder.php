<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Mục đích: tạo tài khoản quản trị viên nền tảng.
// Phụ thuộc: RolePermissionSeeder.
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
    }
}
