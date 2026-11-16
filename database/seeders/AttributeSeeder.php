<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Mục đích: tạo thuộc tính và các giá trị thuộc tính dùng trong demo.
// Phụ thuộc: không.
class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Màu sắc' => [
                'type' => 'color',
                'values' => [
                    ['Đen', '#111827'],
                    ['Trắng', '#F9FAFB'],
                    ['Xanh', '#3B82F6'],
                    ['Đỏ', '#EF4444'],
                    ['Xám', '#6B7280'],
                    ['Titan Tự Nhiên', '#A8A29E'],
                    ['Titan Xanh', '#3B4A5E'],
                    ['Titan Đen', '#1F2937'],
                    ['Titan Tím', '#7C3AED'],
                ],
            ],
            'Kích cỡ' => [
                'type' => 'select',
                'values' => [
                    ['S', null],
                    ['M', null],
                    ['L', null],
                    ['XL', null],
                ],
            ],
            'Dung lượng' => [
                'type' => 'select',
                'values' => [
                    ['64GB', null],
                    ['128GB', null],
                    ['256GB', null],
                    ['512GB', null],
                    ['1TB', null],
                ],
            ],
            'CPU' => [
                'type' => 'select',
                'values' => [
                    ['i5', null],
                    ['i7', null],
                    ['i9', null],
                    ['Ryzen 5', null],
                    ['Ryzen 7', null],
                    ['Ryzen 9', null],
                    ['M1', null],
                    ['M2', null],
                    ['M3 Pro', null],
                    ['M3 Max', null],
                ],
            ],
            'RAM' => [
                'type' => 'select',
                'values' => [
                    ['8GB', null],
                    ['16GB', null],
                    ['18GB', null],
                    ['32GB', null],
                    ['36GB', null],
                ],
            ],
        ];

        foreach ($data as $name => $meta) {
            $slug = Str::slug($name);
            $attr = Attribute::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'type' => $meta['type'], 'sort_order' => 0]
            );

            foreach ($meta['values'] as $i => [$value, $color]) {
                AttributeValue::updateOrCreate(
                    ['attribute_id' => $attr->id, 'slug' => Str::slug($value)],
                    ['value' => $value, 'color_code' => $color, 'sort_order' => $i]
                );
            }
        }
    }
}
