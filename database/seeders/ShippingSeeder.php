<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
// Tạo dữ liệu khu vực và mức phí giao hàng mẫu.

use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'code' => 'MB',
                'name' => 'Miền Bắc',
                'provinces' => ['Hà Nội', 'Hải Phòng', 'Quảng Ninh', 'Bắc Ninh', 'Nam Định'],
                'description' => 'Giao hàng khu vực miền Bắc',
                'sort_order' => 1,
                'rates' => [
                    ['name' => 'Tiêu chuẩn', 'fee' => 30000, 'min_days' => 2, 'max_days' => 4, 'min_val' => 0,       'max_val' => null,     'min_kg' => 0, 'max_kg' => null,  'sort' => 1],
                    ['name' => 'Nhanh',      'fee' => 50000, 'min_days' => 1, 'max_days' => 2, 'min_val' => 500000,  'max_val' => null,     'min_kg' => 0, 'max_kg' => 5000,  'sort' => 2],
                    ['name' => 'Hỏa tốc',    'fee' => 80000, 'min_days' => 1, 'max_days' => 1, 'min_val' => 2000000, 'max_val' => 10000000, 'min_kg' => 0, 'max_kg' => 2000,  'sort' => 3],
                ],
            ],
            [
                'code' => 'MT',
                'name' => 'Miền Trung',
                'provinces' => ['Đà Nẵng', 'Huế', 'Quảng Nam', 'Bình Định', 'Khánh Hòa'],
                'description' => 'Giao hàng khu vực miền Trung',
                'sort_order' => 2,
                'rates' => [
                    ['name' => 'Tiêu chuẩn', 'fee' => 40000, 'min_days' => 3, 'max_days' => 5, 'min_val' => 0,       'max_val' => null, 'min_kg' => 0, 'max_kg' => null, 'sort' => 1],
                    ['name' => 'Nhanh',      'fee' => 65000, 'min_days' => 2, 'max_days' => 3, 'min_val' => 1000000, 'max_val' => null, 'min_kg' => 0, 'max_kg' => 5000, 'sort' => 2],
                ],
            ],
            [
                'code' => 'MN',
                'name' => 'Miền Nam',
                'provinces' => ['TP. Hồ Chí Minh', 'Cần Thơ', 'Bình Dương', 'Đồng Nai', 'Bà Rịa - Vũng Tàu'],
                'description' => 'Giao hàng khu vực miền Nam',
                'sort_order' => 3,
                'rates' => [
                    ['name' => 'Tiêu chuẩn', 'fee' => 35000, 'min_days' => 2, 'max_days' => 4, 'min_val' => 0,       'max_val' => null,     'min_kg' => 0, 'max_kg' => null,  'sort' => 1],
                    ['name' => 'Nhanh',      'fee' => 55000, 'min_days' => 1, 'max_days' => 2, 'min_val' => 500000,  'max_val' => null,     'min_kg' => 0, 'max_kg' => 5000,  'sort' => 2],
                    ['name' => 'Hỏa tốc',    'fee' => 85000, 'min_days' => 1, 'max_days' => 1, 'min_val' => 3000000, 'max_val' => 15000000, 'min_kg' => 0, 'max_kg' => 2000,  'sort' => 3],
                ],
            ],
        ];

        foreach ($zones as $z) {
            $zone = ShippingZone::updateOrCreate(
                ['code' => $z['code']],
                [
                    'name' => $z['name'],
                    'provinces' => $z['provinces'],
                    'description' => $z['description'],
                    'is_active' => true,
                    'sort_order' => $z['sort_order'],
                ]
            );

            foreach ($z['rates'] as $r) {
                ShippingRate::updateOrCreate(
                    ['zone_id' => $zone->id, 'name' => $r['name']],
                    [
                        'fee' => $r['fee'],
                        'estimated_days_min' => $r['min_days'],
                        'estimated_days_max' => $r['max_days'],
                        'min_order_value' => $r['min_val'],
                        'max_order_value' => $r['max_val'],
                        'min_weight_gram' => $r['min_kg'],
                        'max_weight_gram' => $r['max_kg'],
                        'is_active' => true,
                        'sort_order' => $r['sort'],
                    ]
                );
            }
        }

        $this->command->info('✅ ShippingSeeder: '.ShippingZone::count().' zones, '.ShippingRate::count().' rates (đã set min/max order_value + weight_gram).');
    }
}
