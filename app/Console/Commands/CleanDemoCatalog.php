<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Lệnh xóa dữ liệu catalog demo. */
class CleanDemoCatalog extends Command
{
    protected $signature = 'demo:clean
                            {--force : Không hỏi xác nhận}';

    protected $description = 'Xoá toàn bộ catalog + order + inventory để seed lại demo (giữ users, coupons, shipping)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Thao tác này XOÁ TOÀN BỘ catalog, đơn hàng, tồn kho. Tiếp tục?', false)) {
            $this->warn('Đã huỷ.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();

        // Order-related
        DB::table('order_status_history')->truncate();
        DB::table('order_items')->truncate();
        DB::table('orders')->truncate();
        DB::table('coupon_usages')->truncate();

        // Inventory
        DB::table('stock_movements')->truncate();
        DB::table('inventories')->truncate();
        DB::table('stock_alerts')->truncate();

        // Variant relations
        DB::table('variant_attribute_values')->truncate();
        DB::table('product_attributes')->truncate();
        DB::table('product_images')->truncate();
        DB::table('product_variants')->truncate();

        // Product + catalog
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('brands')->truncate();

        // Attributes
        DB::table('attribute_values')->truncate();
        DB::table('attributes')->truncate();

        // Cart + wishlist
        DB::table('wishlists')->truncate();
        DB::table('cart_items')->truncate();
        DB::table('carts')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->info('✅ Đã xoá sạch catalog + order + inventory.');

        return self::SUCCESS;
    }
}
