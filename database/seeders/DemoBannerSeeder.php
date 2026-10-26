<?php

namespace Database\Seeders;

use App\Models\Banner;
// Tạo dữ liệu banner minh họa.

use Illuminate\Database\Seeder;

class DemoBannerSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'title' => 'Khuyến mãi iPhone 15 Pro Max — Giảm đến 3 triệu',
                'image' => 'https://images.unsplash.com/photo-1592286927505-1def25115558?w=1200&h=400&fit=crop&q=80',
                'link' => '/products?q=iPhone',
                'position' => 'home_top',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'MacBook Pro 14 M3 — Trả góp 0%',
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1200&h=400&fit=crop&q=80',
                'link' => '/products?q=MacBook',
                'position' => 'home_top',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Phụ kiện chính hãng — Giảm 20%',
                'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=1200&h=300&fit=crop&q=80',
                'link' => '/products',
                'position' => 'home_middle',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Laptop gaming — Freeship toàn quốc',
                'image' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=1200&h=300&fit=crop&q=80',
                'link' => '/products?category=laptop',
                'position' => 'home_middle',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Banner ẩn (draft)',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200&h=300&fit=crop&q=80',
                'link' => '/products',
                'position' => 'home_bottom',
                'sort_order' => 1,
                'is_active' => false,
            ],
        ];

        foreach ($data as $b) {
            Banner::updateOrCreate(
                ['title' => $b['title']],
                $b
            );
        }

        $this->command->info('Đã seed '.count($data).' banners.');
    }
}
