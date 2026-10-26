<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $coupons = [
            [
                'code' => 'SALE10',
                'name' => 'Giảm 10% đơn từ 100k',
                'description' => 'Áp dụng toàn sàn, giảm tối đa 100.000đ.',
                'type' => 'percent',
                'value' => 10,
                'max_discount_amount' => 100000,
                'min_order_amount' => 100000,
                'usage_limit' => 500,
                'usage_limit_per_user' => 3,
                'starts_at' => $now->copy()->subDays(1),
                'ends_at' => $now->copy()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'SALE20',
                'name' => 'Giảm 20% đơn từ 500k',
                'description' => 'Ưu đãi lớn cho đơn từ 500.000đ, giảm tối đa 200.000đ.',
                'type' => 'percent',
                'value' => 20,
                'max_discount_amount' => 200000,
                'min_order_amount' => 500000,
                'usage_limit' => 200,
                'usage_limit_per_user' => 2,
                'starts_at' => $now->copy()->subDays(3),
                'ends_at' => $now->copy()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP30K',
                'name' => 'Miễn phí vận chuyển 30k',
                'description' => 'Giảm 30.000đ phí ship cho đơn từ 200.000đ.',
                'type' => 'fixed',
                'value' => 30000,
                'max_discount_amount' => null,
                'min_order_amount' => 200000,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 5,
                'starts_at' => $now->copy()->subDays(7),
                'ends_at' => $now->copy()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'WELCOME50K',
                'name' => 'Chào mừng thành viên mới — Giảm 50k',
                'description' => 'Ưu đãi đặc biệt cho khách hàng mới.',
                'type' => 'fixed',
                'value' => 50000,
                'max_discount_amount' => null,
                'min_order_amount' => 300000,
                'usage_limit' => 300,
                'usage_limit_per_user' => 1,
                'starts_at' => $now->copy()->subDays(15),
                'ends_at' => $now->copy()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'TECH50K',
                'name' => 'Ưu đãi công nghệ — Giảm 50k',
                'description' => 'Giảm 50.000đ cho đơn từ 400.000đ.',
                'type' => 'fixed',
                'value' => 50000,
                'max_discount_amount' => null,
                'min_order_amount' => 400000,
                'usage_limit' => 500,
                'usage_limit_per_user' => 3,
                'starts_at' => $now->copy()->subDays(10),
                'ends_at' => $now->copy()->addMonths(4),
                'is_active' => true,
            ],
            [
                'code' => 'BLACKFRIDAY',
                'name' => 'Black Friday — Giảm 30%',
                'description' => 'Sale khủng, giảm tối đa 500.000đ cho đơn từ 1 triệu.',
                'type' => 'percent',
                'value' => 30,
                'max_discount_amount' => 500000,
                'min_order_amount' => 1000000,
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'starts_at' => $now->copy()->addDays(20),
                'ends_at' => $now->copy()->addDays(25),
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER2024',
                'name' => 'Sale hè 2024 (đã hết hạn)',
                'description' => 'Mã cũ — dùng để test logic check thời gian.',
                'type' => 'percent',
                'value' => 15,
                'max_discount_amount' => 100000,
                'min_order_amount' => 200000,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 5,
                'starts_at' => $now->copy()->subMonths(6),
                'ends_at' => $now->copy()->subMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'PAUSED50K',
                'name' => 'Mã tạm dừng (test)',
                'description' => 'Mã bị admin tắt — dùng để test logic check is_active.',
                'type' => 'fixed',
                'value' => 50000,
                'max_discount_amount' => null,
                'min_order_amount' => 100000,
                'usage_limit' => null,
                'usage_limit_per_user' => null,
                'starts_at' => $now->copy()->subDays(5),
                'ends_at' => $now->copy()->addMonths(3),
                'is_active' => false,
            ],
        ];

        $created = 0;
        foreach ($coupons as $data) {
            $existing = Coupon::where('code', $data['code'])->first();
            $data['used_count'] = $existing->used_count ?? 0;
            Coupon::updateOrCreate(['code' => $data['code']], $data);
            $created++;
        }

        $this->command->info("Đã seed {$created} mã giảm giá (5 đang chạy, 1 sắp chạy, 1 hết hạn, 1 tạm dừng).");
    }
}
