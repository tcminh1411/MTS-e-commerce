<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
// Tạo dữ liệu danh mục và thương hiệu mẫu.

use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ============ CATEGORIES ============
        $tree = [
            [
                'name' => 'Điện thoại', 'slug' => 'dien-thoai',
                'image_url' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop&q=80',
                'children' => [
                    ['name' => 'Smartphone', 'slug' => 'smartphone',
                        'image_url' => 'https://images.unsplash.com/photo-1592286927505-1def25115558?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Điện thoại phổ thông', 'slug' => 'dien-thoai-pho-thong',
                        'image_url' => 'https://images.unsplash.com/photo-1520923642038-b4259acecbd7?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Điện thoại Gaming', 'slug' => 'dien-thoai-gaming',
                        'image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400&h=400&fit=crop&q=80'],
                ],
            ],
            [
                'name' => 'Laptop', 'slug' => 'laptop',
                'image_url' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=400&fit=crop&q=80',
                'children' => [
                    ['name' => 'Laptop Gaming', 'slug' => 'laptop-gaming',
                        'image_url' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Laptop Văn phòng', 'slug' => 'laptop-van-phong',
                        'image_url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'MacBook', 'slug' => 'macbook',
                        'image_url' => 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=400&h=400&fit=crop&q=80'],
                ],
            ],
            [
                'name' => 'Tablet', 'slug' => 'tablet',
                'image_url' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400&h=400&fit=crop&q=80',
                'children' => [
                    ['name' => 'iPad', 'slug' => 'ipad',
                        'image_url' => 'https://images.unsplash.com/photo-1561154464-82e9adf32764?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Android Tablet', 'slug' => 'android-tablet',
                        'image_url' => 'https://images.unsplash.com/photo-1585790050230-5dd28404ccb9?w=400&h=400&fit=crop&q=80'],
                ],
            ],
            [
                'name' => 'Phụ kiện', 'slug' => 'phu-kien',
                'image_url' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=400&h=400&fit=crop&q=80',
                'children' => [
                    ['name' => 'Tai nghe', 'slug' => 'tai-nghe',
                        'image_url' => 'https://images.unsplash.com/photo-1487215078519-e21cc028cb29?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Sạc & Cáp', 'slug' => 'sac-cap',
                        'image_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Ốp lưng & Bao da', 'slug' => 'op-lung-bao-da',
                        'image_url' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Chuột & Bàn phím', 'slug' => 'chuot-ban-phim',
                        'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400&h=400&fit=crop&q=80'],
                ],
            ],
            [
                'name' => 'Âm thanh', 'slug' => 'am-thanh',
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=400&fit=crop&q=80',
                'children' => [
                    ['name' => 'Loa Bluetooth', 'slug' => 'loa-bluetooth',
                        'image_url' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400&h=400&fit=crop&q=80'],
                    ['name' => 'Tai nghe True Wireless', 'slug' => 'tws',
                        'image_url' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=400&h=400&fit=crop&q=80'],
                ],
            ],
            [
                'name' => 'Đồng hồ', 'slug' => 'dong-ho',
                'image_url' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=400&fit=crop&q=80',
                'children' => [
                    ['name' => 'Smartwatch', 'slug' => 'smartwatch',
                        'image_url' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=400&h=400&fit=crop&q=80'],
                ],
            ],
        ];

        $parentSort = 0;
        foreach ($tree as $node) {
            $parent = Category::create([
                'name' => $node['name'],
                'slug' => $node['slug'],
                'image_url' => $node['image_url'],
                'sort_order' => $parentSort,
                'is_active' => true,
                'description' => 'Danh mục '.$node['name'],
            ]);
            $parentSort += 10;

            $childSort = 0;
            foreach ($node['children'] as $child) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $child['name'],
                    'slug' => $child['slug'],
                    'image_url' => $child['image_url'],
                    'sort_order' => $childSort,
                    'is_active' => true,
                    'description' => 'Danh mục '.$child['name'],
                ]);
                $childSort += 10;
            }
        }

        // ============ BRANDS ============
        $brands = [
            ['Apple',     'apple',     'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=200&h=100&fit=crop&q=80'],
            ['Samsung',   'samsung',   'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=200&h=100&fit=crop&q=80'],
            ['Xiaomi',    'xiaomi',    'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=200&h=100&fit=crop&q=80'],
            ['OPPO',      'oppo',      'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=200&h=100&fit=crop&q=80'],
            ['Dell',      'dell',      'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=200&h=100&fit=crop&q=80'],
            ['Asus',      'asus',      'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200&h=100&fit=crop&q=80'],
            ['HP',        'hp',        'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=200&h=100&fit=crop&q=80'],
            ['Lenovo',    'lenovo',    'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=200&h=100&fit=crop&q=80'],
            ['Logitech',  'logitech',  'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=200&h=100&fit=crop&q=80'],
            ['Sony',      'sony',      'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200&h=100&fit=crop&q=80'],
            ['JBL',       'jbl',       'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=200&h=100&fit=crop&q=80'],
            ['Anker',     'anker',     'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=200&h=100&fit=crop&q=80'],
            ['Baseus',    'baseus',    'https://images.unsplash.com/photo-1609592806596-b43bada2f2e3?w=200&h=100&fit=crop&q=80'],
            ['Realme',    'realme',    'https://images.unsplash.com/photo-1592286927505-1def25115558?w=200&h=100&fit=crop&q=80'],
            ['Vivo',      'vivo',      'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=200&h=100&fit=crop&q=80'],
            ['Acer',      'acer',      'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=200&h=100&fit=crop&q=80'],
            ['MSI',       'msi',       'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=200&h=100&fit=crop&q=80'],
            ['Razer',     'razer',     'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=200&h=100&fit=crop&q=80'],
            ['Kingston',  'kingston',  'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?w=200&h=100&fit=crop&q=80'],
            ['SanDisk',   'sandisk',   'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?w=200&h=100&fit=crop&q=80'],
        ];

        foreach ($brands as [$name, $slug, $logo]) {
            Brand::create([
                'name' => $name,
                'slug' => $slug,
                'logo_url' => $logo,
                'description' => 'Thương hiệu '.$name,
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ DemoCatalogSeeder: '
            .Category::count().' categories, '
            .Brand::count().' brands.');
    }
}
