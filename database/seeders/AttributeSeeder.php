<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
// Tạo dữ liệu thuộc tính và giá trị thuộc tính mẫu.

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Màu sắc' => [
                'type' => 'color',
                'values' => [
                    ['Đỏ',   '#EF4444'],
                    ['Xanh', '#3B82F6'],
                    ['Đen',  '#111827'],
                    ['Trắng', '#F9FAFB'],
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
                ],
            ],
        ];

        foreach ($data as $name => $meta) {
            $attr = Attribute::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $meta['type'],
                'sort_order' => 0,
            ]);

            foreach ($meta['values'] as $i => [$value, $color]) {
                AttributeValue::create([
                    'attribute_id' => $attr->id,
                    'value' => $value,
                    'slug' => Str::slug($value),
                    'color_code' => $color,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
