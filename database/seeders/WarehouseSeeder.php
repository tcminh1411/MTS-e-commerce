<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

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

        $warehouseIds = Warehouse::pluck('id', 'code');
        $variantIds = ProductVariant::pluck('id');
        $created = 0;

        foreach ($warehouseIds as $code => $warehouseId) {
            $existing = Inventory::where('warehouse_id', $warehouseId)
                ->pluck('product_variant_id')
                ->toArray();

            foreach ($variantIds as $vid) {
                if (in_array($vid, $existing, true)) {
                    continue;
                }

                Inventory::create([
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $vid,
                    'quantity' => 0,
                    'low_stock_threshold' => 5,
                ]);
                $created++;
            }
        }

        $this->command->info(
            'Đã seed '.count($warehouses).' kho (HN-01, HCM-01, CT-01). Inventories created: '.$created
        );
    }
}
