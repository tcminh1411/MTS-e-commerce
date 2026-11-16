<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Mục đích: tạo catalog, giao dịch và nội dung demo.
// Phụ thuộc: ProductionSeeder.
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AttributeSeeder::class,
            DemoCatalogSeeder::class,
            DemoProductSeeder::class,
            CouponSeeder::class,
            DemoBaseUserSeeder::class,
            DemoUserSeeder::class,
            DemoStockSeeder::class,
            DemoOrderSeeder::class,
            DemoReviewSeeder::class,
            DemoReturnRequestSeeder::class,
            DemoPersonalDataSeeder::class,
            DemoFaqSeeder::class,
            DemoBannerSeeder::class,
            DemoPageSeeder::class,
            DemoPostCategorySeeder::class,
            DemoPostSeeder::class,
        ]);
    }
}
