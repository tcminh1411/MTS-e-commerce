<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ShippingRate;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\Wishlist;
// Tạo dữ liệu cá nhân mẫu phục vụ môi trường demo.

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoPersonalDataSeeder extends Seeder
{
    private const STATUSES_ADMIN = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];

    private const STATUSES_TCMINH = [
        'pending', 'pending', 'confirmed', 'confirmed',
        'shipping', 'shipping', 'completed', 'cancelled',
    ];

    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        if (! $admin) {
            $this->command->error('❌ admin@example.com không tồn tại — chạy UserSeeder trước.');

            return;
        }

        $tcminh = User::firstOrCreate(
            ['email' => 'tcminh@example.com'],
            [
                'full_name' => 'Thái Cao Minh',
                'password' => Hash::make('mật khẩu'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $tcminh->assignRole('customer');

        // Account thực của owner (email real)
        $tcminh1411 = User::firstOrCreate(
            ['email' => 'tcminh1411@gmail.com'],
            [
                'full_name' => 'Thái Cao Minh',
                'password' => Hash::make('mật khẩu'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $tcminh1411->assignRole('customer');
        $this->command->line(
            '  · tcminh1411@gmail.com: '.($tcminh1411->wasRecentlyCreated ? 'created' : 'already exists')
        );

        $this->command->line(
            '  · tcminh@example.com: '.($tcminh->wasRecentlyCreated ? 'created (password: mật khẩu)' : 'already exists')
            ." (name: {$tcminh->full_name})"
        );

        $this->seedCart($admin, 2);
        $this->seedCart($tcminh, 3);
        $this->seedCart($tcminh1411, 3);

        $this->seedWishlist($admin, 5);
        $this->seedWishlist($tcminh, 8);
        $this->seedWishlist($tcminh1411, 8);

        $adminOrders = $this->seedOrders($admin, self::STATUSES_ADMIN, 'ADM');
        $tcminhOrders = $this->seedOrders($tcminh, self::STATUSES_TCMINH, 'TCM');
        $tcminh1411Orders = $this->seedOrders($tcminh1411, self::STATUSES_TCMINH, 'TC11');

        $this->seedReviews($admin, $adminOrders);
        $this->seedReviews($tcminh, $tcminhOrders);
        $this->seedReviews($tcminh1411, $tcminh1411Orders);

        $this->seedStockAlerts($tcminh, 2);

        $this->command->info(sprintf(
            '✅ DemoPersonalDataSeeder: admin=%d orders / tcminh=%d orders / wishlists=%d / reviews=%d / alerts=%d',
            Order::where('user_id', $admin->id)->count(),
            Order::where('user_id', $tcminh->id)->count(),
            Wishlist::whereIn('user_id', [$admin->id, $tcminh->id])->count(),
            Review::whereIn('user_id', [$admin->id, $tcminh->id])->count(),
            StockAlert::where('user_id', $tcminh->id)->count(),
        ));
    }

    private function seedCart(User $user, int $count): void
    {
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'VND']
        );

        $existingProductIds = $cart->items()->pluck('product_id')->all();
        $need = $count - count($existingProductIds);
        if ($need <= 0) {
            return;
        }

        $products = Product::active()
            ->whereNotIn('id', $existingProductIds)
            ->with('defaultVariant')
            ->inRandomOrder()
            ->limit($need)
            ->get();

        foreach ($products as $p) {
            $variant = $p->defaultVariant;
            $price = $variant?->final_price ?? $p->final_price;

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $p->id,
                'variant_id' => $variant?->id,
                'quantity' => random_int(1, 2),
                'unit_price' => $price,
            ]);
        }
    }

    private function seedWishlist(User $user, int $count): void
    {
        $existingIds = Wishlist::where('user_id', $user->id)->pluck('product_id')->all();
        $need = $count - count($existingIds);
        if ($need <= 0) {
            return;
        }

        $products = Product::active()
            ->whereNotIn('id', $existingIds)
            ->inRandomOrder()
            ->limit($need)
            ->get();

        foreach ($products as $p) {
            Wishlist::firstOrCreate([
                'user_id' => $user->id,
                'product_id' => $p->id,
            ]);
        }
    }

    private function seedOrders(User $user, array $statuses, string $prefix): array
    {
        $orders = [];
        $rates = ShippingRate::all();
        if ($rates->isEmpty()) {
            $this->command->warn("  ⚠ Không có shipping rate — skip orders cho {$user->email}");

            return [];
        }

        foreach ($statuses as $i => $status) {
            $orderNumber = 'ORD-'.$prefix.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $existing = Order::where('order_number', $orderNumber)->first();
            if ($existing) {
                $orders[] = $existing;

                continue;
            }

            $variants = ProductVariant::with('product')
                ->where('stock', '>', 0)
                ->inRandomOrder()
                ->limit(random_int(1, 2))
                ->get();

            if ($variants->isEmpty()) {
                continue;
            }

            $subtotal = 0;
            $items = [];
            foreach ($variants as $v) {
                $qty = random_int(1, 2);
                $price = (int) $v->final_price ?: 100000;
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;
                $items[] = ['variant' => $v, 'qty' => $qty, 'price' => $price, 'line' => $lineTotal];
            }

            $rate = $rates->random();
            $shipping = (int) $rate->fee;
            $total = $subtotal + $shipping;

            $paymentStatus = in_array($status, ['completed', 'shipping'], true) ? 'paid' : 'unpaid';
            $paymentMethod = random_int(1, 10) <= 6 ? 'cod' : 'vnpay';
            $placedAt = now()->subDays(random_int(1, 45))->setTime(random_int(8, 21), random_int(0, 59));

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'guest_email' => null,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'shipping_fee' => $shipping,
                'total' => $total,
                'shipping_zone_id' => $rate->zone_id,
                'shipping_rate_id' => $rate->id,
                'shipping_rate_name' => $rate->name,
                'recipient_name' => $user->full_name ?? 'Khách',
                'recipient_phone' => '0900000000',
                'recipient_email' => $user->email,
                'shipping_address' => 'Địa chỉ demo — '.$user->email,
                'placed_at' => $placedAt,
            ]);

            DB::table('orders')->where('id', $order->id)->update([
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            foreach ($items as $row) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $row['variant']->product?->id,
                    'variant_id' => $row['variant']->id,
                    'product_name' => $row['variant']->product?->name ?? '(N/A)',
                    'variant_name' => $row['variant']->name,
                    'sku' => $row['variant']->product?->sku,
                    'unit_price' => $row['price'],
                    'quantity' => $row['qty'],
                    'line_total' => $row['line'],
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'note' => 'Đơn hàng được tạo.',
                'changed_by_user_id' => null,
            ]);
            if ($status !== 'pending') {
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => 'pending',
                    'to_status' => $status,
                    'note' => null,
                    'changed_by_user_id' => null,
                ]);
            }

            $orders[] = $order;
        }

        return $orders;
    }

    private function seedReviews(User $user, array $orders): void
    {
        foreach ($orders as $order) {
            if ($order->status !== 'completed') {
                continue;
            }
            $item = $order->items()->first();
            if (! $item || ! $item->product_id) {
                continue;
            }

            Review::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                ],
                [
                    'rating' => random_int(4, 5),
                    'comment' => 'Sản phẩm tốt, giao nhanh. Sẽ ủng hộ tiếp.',
                    'is_visible' => true,
                ]
            );
        }
    }

    private function seedStockAlerts(User $user, int $count): void
    {
        $existingVariantIds = StockAlert::where('user_id', $user->id)
            ->whereNotNull('variant_id')
            ->pluck('variant_id')
            ->all();
        $need = $count - count($existingVariantIds);
        if ($need <= 0) {
            return;
        }

        $variants = ProductVariant::where('stock', '>', 0)
            ->whereNotIn('id', $existingVariantIds)
            ->inRandomOrder()
            ->limit($need)
            ->get();

        foreach ($variants as $v) {
            StockAlert::firstOrCreate(
                [
                    'product_id' => $v->product_id,
                    'variant_id' => $v->id,
                    'email' => $user->email,
                ],
                [
                    'user_id' => $user->id,
                    'notified_at' => null,
                ]
            );
        }
    }
}
