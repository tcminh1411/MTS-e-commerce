<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Faker\Factory;
// Tạo dữ liệu đơn hàng mẫu phục vụ kiểm thử và demo.

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoOrderSeeder extends Seeder
{
    /**
     * Tạo 150 orders demo trải 60 ngày gần đây.
     * User = các tài khoản @demo.local (từ DemoUserSeeder).
     * Idempotent: nếu đã có order của demo user → skip.
     */
    public function run(): void
    {
        $demoUserIds = User::where('email', 'like', '%@demo.local')->pluck('id')->all();

        if (empty($demoUserIds)) {
            $this->command->error('❌ Chưa có user @demo.local — chạy DemoUserSeeder trước.');

            return;
        }

        if (Order::whereIn('user_id', $demoUserIds)->exists()) {
            $this->command->warn('⚠️  Đã có orders demo — bỏ qua (idempotent).');

            return;
        }

        $faker = Factory::create('vi_VN');

        $variants = ProductVariant::with('product:id,name,sku')
            ->where('stock', '>', 0)
            ->get();

        if ($variants->isEmpty()) {
            $this->command->error('❌ Không có variant nào stock > 0.');

            return;
        }

        $rates = ShippingRate::all();
        if ($rates->isEmpty()) {
            $this->command->error('❌ Chưa có shipping rates.');

            return;
        }

        // Weighted status pool: 60% completed, 10% shipping, 10% confirmed, 5% pending, 15% cancelled
        $statusPool = array_merge(
            array_fill(0, 60, 'completed'),
            array_fill(0, 10, 'shipping'),
            array_fill(0, 10, 'confirmed'),
            array_fill(0, 5, 'pending'),
            array_fill(0, 15, 'cancelled'),
        );

        $totalOrders = 150;
        $daysBack = 60;
        $created = 0;

        DB::transaction(function () use (
            $demoUserIds, $faker, $variants, $rates, $statusPool,
            $totalOrders, $daysBack, &$created
        ) {
            for ($i = 0; $i < $totalOrders; $i++) {
                $daysAgo = random_int(0, $daysBack);
                $placedAt = now()->subDays($daysAgo)->setTime(
                    random_int(7, 22), random_int(0, 59), random_int(0, 59)
                );

                $userId = $demoUserIds[array_rand($demoUserIds)];
                $user = User::find($userId);

                $status = $statusPool[array_rand($statusPool)];
                $paymentMethod = (random_int(1, 10) <= 6) ? 'cod' : 'vnpay';

                if (in_array($status, ['completed', 'shipping'], true)) {
                    $paymentStatus = 'paid';
                } elseif ($status === 'cancelled') {
                    $paymentStatus = 'unpaid';
                } elseif ($paymentMethod === 'vnpay' && $status === 'confirmed') {
                    $paymentStatus = 'paid';
                } else {
                    $paymentStatus = 'unpaid';
                }

                $rate = $rates->random();

                // Items: 1-3 variant
                $itemCount = random_int(1, 3);
                $picked = $variants->random(min($itemCount, $variants->count()));
                if (! $picked instanceof Collection) {
                    $picked = collect([$picked]);
                }

                $subtotal = 0;
                $itemsData = [];
                foreach ($picked as $variant) {
                    $qty = random_int(1, 2);
                    $unitPrice = (int) $variant->final_price;
                    if ($unitPrice <= 0) {
                        $unitPrice = 100000;
                    }
                    $lineTotal = $unitPrice * $qty;
                    $subtotal += $lineTotal;

                    $itemsData[] = [
                        'product' => $variant->product,
                        'variant' => $variant,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                // Coupon SALE10: 10% đơn có subtotal >= 100k
                $discount = 0;
                $couponCode = null;
                if (random_int(1, 10) === 1 && $subtotal >= 100000) {
                    $discount = (int) min($subtotal * 0.1, 100000);
                    $couponCode = 'SALE10';
                }

                $shippingFee = (int) $rate->fee;
                $total = $subtotal - $discount + $shippingFee;

                $orderNumber = 'ORD-'.$placedAt->format('Ymd')
                    .'-'.str_pad((string) (1000 + $i + 1), 4, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'user_id' => $userId,
                    'guest_email' => null,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'shipping_fee' => $shippingFee,
                    'total' => $total,
                    'coupon_code' => $couponCode,
                    'shipping_zone_id' => $rate->zone_id,
                    'shipping_rate_id' => $rate->id,
                    'shipping_rate_name' => $rate->name,
                    'recipient_name' => $user->full_name ?? $faker->name(),
                    'recipient_phone' => '0'.random_int(900000000, 999999999),
                    'recipient_email' => $user->email,
                    'shipping_address' => $faker->address(),
                    'note' => null,
                    'placed_at' => $placedAt,
                ]);

                // Ghi đè timestamps order theo placed_at
                DB::table('orders')->where('id', $order->id)->update([
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);

                // OrderItems
                foreach ($itemsData as $row) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $row['product']?->id,
                        'variant_id' => $row['variant']->id,
                        'product_name' => $row['product']?->name ?? '(N/A)',
                        'variant_name' => $row['variant']->getAttribute('name'),
                        'sku' => $row['product']?->sku,
                        'unit_price' => $row['unit_price'],
                        'quantity' => $row['qty'],
                        'line_total' => $row['line_total'],
                    ]);
                }

                $this->recordHistory($order, $status, $placedAt);
                $created++;
            }
        });

        $this->command->info("✅ DemoOrderSeeder: tạo {$created} orders.");
    }

    /**
     * Ghi timeline trạng thái. Mỗi bước cách nhau vài giờ để trông tự nhiên.
     */
    private function recordHistory(Order $order, string $finalStatus, $placedAt): void
    {
        $steps = match ($finalStatus) {
            'confirmed' => ['pending', 'confirmed'],
            'shipping' => ['pending', 'confirmed', 'shipping'],
            'completed' => ['pending', 'confirmed', 'shipping', 'completed'],
            'cancelled' => ['pending', 'cancelled'],
            default => ['pending'],
        };

        $from = null;
        $stepTime = $placedAt->copy();

        foreach ($steps as $idx => $to) {
            $history = OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $idx === 0 ? 'Đơn hàng được tạo.' : null,
                'changed_by_user_id' => null,
            ]);

            DB::table('order_status_history')->where('id', $history->id)->update([
                'created_at' => $stepTime,
                'updated_at' => $stepTime,
            ]);

            $from = $to;
            $stepTime = $stepTime->copy()->addHours(random_int(4, 48));
        }
    }
}
