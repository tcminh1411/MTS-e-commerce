<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Mục đích: tạo role, permission, admin, khu vực giao hàng và kho cho production.
// Phụ thuộc: các bảng ứng dụng đã migrate.
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            ShippingSeeder::class,
            WarehouseSeeder::class,
        ]);
    }
}
