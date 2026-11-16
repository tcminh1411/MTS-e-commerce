<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

// Mục đích: tạo dữ liệu kho nền tảng.
// Phụ thuộc: không.
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'HN-01',
                'name' => 'Kho Hà Nội',
                'address' => '123 Đường Láng, Đống Đa, Hà Nội',
                'phone' => '0901234567',
                'is_active' => true,
            ],
            [
                'code' => 'HCM-01',
                'name' => 'Kho TP. Hồ Chí Minh',
                'address' => '456 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh',
                'phone' => '0901234568',
                'is_active' => true,
            ],
            [
                'code' => 'CT-01',
                'name' => 'Kho Cần Thơ',
                'address' => '789 Hòa Bình, Ninh Kiều, Cần Thơ',
                'phone' => '0901234569',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $data) {
            Warehouse::firstOrCreate(['code' => $data['code']], $data);
        }

        $this->command->info('Đã seed '.count($warehouses).' kho (HN-01, HCM-01, CT-01).');
    }
}
