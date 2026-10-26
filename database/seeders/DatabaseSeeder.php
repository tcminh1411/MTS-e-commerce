<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ========== 1. BASE DATA (bắt buộc — chạy cả trên production) ==========
            RolePermissionSeeder::class,    // roles + permissions Spatie
            AttributeSeeder::class,         // thuộc tính sản phẩm (màu sắc, kích cỡ, ...)
            ShippingSeeder::class,          // shipping zones + rates
            WarehouseSeeder::class,         // 3 kho: HN-01, HCM-01, CT-01
            CouponSeeder::class,            // 8 mã giảm giá (SALE10, SALE20, ...)
            UserSeeder::class,              // admin + staff + 8 factory customer

            // ========== 2. DEMO USERS (phải chạy TRƯỚC orders) ==========
            DemoUserSeeder::class,          // 30 customer @demo.local

            // ========== 3. DEMO CATALOG ==========
            DemoCatalogSeeder::class,       // 21 categories + 20 brands
            DemoProductSeeder::class,       // products + variants + images
            DemoStockSeeder::class,         // tồn kho phân bổ 60/30/10 cho 3 kho

            // ========== 4. DEMO TRANSACTIONS ==========
            DemoOrderSeeder::class,         // 150 orders trải 60 ngày
            DemoReviewSeeder::class,        // reviews từ orders completed
            DemoReturnRequestSeeder::class, // 15 yêu cầu trả hàng (đủ 4 trạng thái)
            DemoPersonalDataSeeder::class,  // orders + wishlist riêng admin/tcminh1411

            // ========== 5. CMS ==========
            DemoFaqSeeder::class,
            DemoBannerSeeder::class,
            DemoPageSeeder::class,
            DemoPostCategorySeeder::class,
            DemoPostSeeder::class,
        ]);
    }
}
