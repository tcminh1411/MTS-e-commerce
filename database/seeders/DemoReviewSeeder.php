<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use RuntimeException;

// Mục đích: tạo đánh giá cho đơn hàng demo đã hoàn tất.
// Phụ thuộc: DemoOrderSeeder, DemoProductSeeder.
class DemoReviewSeeder extends Seeder
{
    private const RATING_WEIGHTS = [
        5 => 60,
        4 => 30,
        3 => 10,
    ];

    /**
     * Tạo đánh giá hiển thị cho các đơn hàng đã hoàn tất.
     * Idempotent: chạy lại không tạo duplicate.
     * Sau khi seed → recalculate rating_avg + rating_count cho mọi product có review.
     */
    public function run(): void
    {
        $comments = [
            'Sản phẩm rất tốt, đóng gói cẩn thận, giao hàng nhanh. Sẽ ủng hộ tiếp!',
            'Chất lượng vượt mong đợi, đúng như mô tả. Rất đáng tiền.',
            'Hàng chính hãng, dùng ổn định. Shop tư vấn nhiệt tình.',
            'Giao hàng nhanh, sản phẩm đẹp, đóng gói chắc chắn.',
            'Mua lần 2 rồi, vẫn rất hài lòng. Recommend cho mọi người.',
            'Pin trâu, hiệu năng mượt, đáng đồng tiền.',
            'Thiết kế đẹp, cầm chắc tay. Shop uy tín.',
            'Đúng mẫu, đúng giá, không có gì để chê.',
            'Nhận hàng nhanh, sản phẩm nguyên seal. Rất hài lòng.',
            'Giá tốt, chất lượng ổn. Sẽ giới thiệu bạn bè.',
        ];

        $orders = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('user_id')
            ->with(['items' => fn ($q) => $q->whereNotNull('product_id')])
            ->get();

        if ($orders->isEmpty()) {
            throw new RuntimeException('Chạy DemoOrderSeeder trước DemoReviewSeeder.');
        }

        $created = 0;
        $skipped = 0;

        $ratingPool = [];
        foreach (self::RATING_WEIGHTS as $rating => $weight) {
            $ratingPool = array_merge($ratingPool, array_fill(0, $weight, $rating));
        }

        foreach ($orders as $order) {
            if ($order->items->isEmpty()) {
                continue;
            }

            $pickCount = random_int(1, min(2, $order->items->count()));
            $pickedItems = $order->items->random($pickCount);

            foreach ($pickedItems as $item) {
                $exists = Review::where('user_id', $order->user_id)
                    ->where('product_id', $item->product_id)
                    ->where('order_id', $order->id)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Review::create([
                    'user_id' => $order->user_id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'rating' => $ratingPool[array_rand($ratingPool)],
                    'comment' => $comments[array_rand($comments)],
                    'is_visible' => true,
                ]);
                $created++;
            }
        }

        // Recalculate rating cho tất cả product có review approved
        $productIds = Review::where('is_visible', true)
            ->distinct()
            ->pluck('product_id')
            ->filter();

        foreach ($productIds as $pid) {
            Product::find($pid)?->recalculateRating();
        }

        $this->command->info("✅ DemoReviewSeeder: tạo {$created} đánh giá (bỏ qua {$skipped} đánh giá trùng).");
        $this->command->info("   Đã recalculate rating cho {$productIds->count()} products.");
    }
}
