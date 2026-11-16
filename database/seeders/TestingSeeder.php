<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Mục đích: tạo nhanh dữ liệu tối thiểu dùng cho kiểm thử.
// Phụ thuộc: schema đã migrate; tự tạo role và permission.
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'full_name' => 'Quản trị viên',
                'phone' => '0900000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $customer = User::updateOrCreate(
            ['email' => 'customer@testing.local'],
            [
                'full_name' => 'Khách kiểm thử',
                'phone' => '0900000003',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $customer->assignRole('customer');

        $category = Category::updateOrCreate(
            ['slug' => 'testing-category'],
            [
                'name' => 'Danh mục kiểm thử',
                'description' => 'Dữ liệu tối thiểu cho kiểm thử.',
                'is_active' => true,
            ]
        );

        $brand = Brand::updateOrCreate(
            ['slug' => 'testing-brand'],
            [
                'name' => 'Thương hiệu kiểm thử',
                'is_active' => true,
            ]
        );

        $product = Product::updateOrCreate(
            ['slug' => 'testing-product'],
            [
                'sku' => 'TEST-PRODUCT',
                'name' => 'Sản phẩm kiểm thử',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'product_type' => 'variable',
                'price' => 100000,
                'currency' => 'VND',
                'status' => 'active',
                'is_featured' => false,
            ]
        );

        $warehouse = Warehouse::updateOrCreate(
            ['code' => 'TEST-01'],
            [
                'name' => 'Kho kiểm thử',
                'address' => 'Địa chỉ kiểm thử',
                'phone' => '0900000004',
                'is_active' => true,
            ]
        );

        foreach ([
            ['sku' => 'TEST-PRODUCT-01', 'name' => 'Biến thể 1', 'sort_order' => 0, 'is_default' => true],
            ['sku' => 'TEST-PRODUCT-02', 'name' => 'Biến thể 2', 'sort_order' => 1, 'is_default' => false],
        ] as $variantData) {
            $variant = ProductVariant::updateOrCreate(
                ['sku' => $variantData['sku']],
                [
                    'product_id' => $product->id,
                    'name' => $variantData['name'],
                    'price' => 100000,
                    'is_default' => $variantData['is_default'],
                    'stock' => 5,
                    'status' => 'active',
                    'sort_order' => $variantData['sort_order'],
                ]
            );

            Inventory::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $variant->id,
                ],
                [
                    'quantity' => 5,
                    'low_stock_threshold' => 1,
                ]
            );
        }

        $zone = ShippingZone::updateOrCreate(
            ['code' => 'TEST'],
            [
                'name' => 'Khu vực kiểm thử',
                'provinces' => ['Hà Nội'],
                'description' => 'Khu vực giao hàng kiểm thử.',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        ShippingRate::updateOrCreate(
            ['zone_id' => $zone->id, 'name' => 'Tiêu chuẩn'],
            [
                'fee' => 30000,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'min_order_value' => 0,
                'min_weight_gram' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'TEST10'],
            [
                'name' => 'Mã kiểm thử',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 0,
                'used_count' => 0,
                'is_active' => true,
            ]
        );
    }
}
