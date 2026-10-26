<?php

namespace Database\Seeders;

use App\Models\PostCategory;
// Tạo danh mục bài viết mẫu.

use Illuminate\Database\Seeder;

class DemoPostCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tin công nghệ',
                'slug' => 'tin-cong-nghe',
                'description' => 'Tin tức, xu hướng công nghệ mới nhất.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Đánh giá sản phẩm',
                'slug' => 'danh-gia-san-pham',
                'description' => 'Review chi tiết các sản phẩm công nghệ.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Hướng dẫn',
                'slug' => 'huong-dan',
                'description' => 'Mẹo vặt và hướng dẫn sử dụng thiết bị.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Khuyến mãi',
                'slug' => 'khuyen-mai',
                'description' => 'Chương trình giảm giá, ưu đãi mới nhất.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Khác',
                'slug' => 'khac',
                'description' => 'Các bài viết không thuộc danh mục nào khác.',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $data) {
            PostCategory::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('DemoPostCategorySeeder: đã seed '.count($categories).' danh mục blog.');
    }
}
