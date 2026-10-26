<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Xử lý nghiệp vụ đơn hàng dùng chung. */
class OrderService
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService,
    ) {}

    /**
     * Tạo đơn hàng từ cart hiện tại.
     *
     * @param array{
     *   recipient_name: string,
     *   recipient_phone: string,
     *   recipient_email?: ?string,
     *   shipping_address: string,
     *   shipping_rate_id: int,
     *   payment_method: string,
     *   note?: ?string,
     * } $data
     */
    public function placeOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $cart = $this->cartService->current();

            // Lock items để tránh race condition khi 2 request cùng checkout
            $items = $cart->items()->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw new RuntimeException('Giỏ hàng đang trống, không thể đặt hàng.');
            }

            // Tính subtotal + tổng weight TRƯỚC để validate rate khả dụng
            $subtotal = (int) $items->sum(fn ($i) => $i->unit_price * $i->quantity);
            $totalWeightGram = (int) $items->sum(function ($i) {
                $w = $i->variant?->weight_gram ?? $i->product?->weight_gram ?? 0;

                return $i->quantity * $w;
            });

            // Fetch rate + validate khả dụng cho subtotal & weight (defensive — tránh user submit rate_id cũ)
            $rate = ShippingRate::forSubtotal($subtotal)
                ->forWeight($totalWeightGram)
                ->find($data['shipping_rate_id']);

            if (! $rate) {
                throw new RuntimeException('Phương thức vận chuyển không khả dụng cho đơn hàng này. Vui lòng chọn lại.');
            }

            $shippingFee = (int) $rate->fee;

            // Áp dụng coupon (nếu user nhập mã)
            $discount = 0;
            $couponCode = null;
            $couponModel = null;

            if (! empty($data['coupon_code'])) {
                // Gán items đã lock vào cart để CouponService dùng luôn, tránh query lại
                $cart->setRelation('items', $items);
                $cart->loadMissing('items.product');

                $couponResult = $this->couponService->apply(
                    $data['coupon_code'],
                    $cart,
                    Auth::user(),
                );

                if (! $couponResult['ok']) {
                    throw new RuntimeException($couponResult['message']);
                }

                $discount = $couponResult['discount'];
                $couponCode = $couponResult['coupon']->code;
                $couponModel = $couponResult['coupon'];
            }

            $total = $subtotal - $discount + $shippingFee;

            // Tạo Order (snapshot địa chỉ + phí ship + tên rate)
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => Auth::id(),                          // null nếu guest
                'guest_email' => Auth::check() ? null : ($data['recipient_email'] ?? null),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'coupon_code' => $couponCode,
                'shipping_zone_id' => $rate->zone_id,
                'shipping_rate_id' => $rate->id,
                'shipping_rate_name' => $rate->name,
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'recipient_email' => $data['recipient_email'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'note' => $data['note'] ?? null,
                'placed_at' => now(),
            ]);

            // Tạo OrderItems (snapshot)
            foreach ($items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product?->name ?? '(sản phẩm đã xoá)',
                    // getAttribute() an toàn nếu ProductVariant không có cột 'name'
                    'variant_name' => $variant?->getAttribute('name'),
                    'sku' => $product?->sku,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->unit_price * $item->quantity,
                ]);
            }

            // Ghi history lần đầu
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'note' => 'Đơn hàng được tạo.',
                'changed_by_user_id' => Auth::id(),
            ]);

            // Ghi nhận lượt dùng coupon (tăng used_count + insert coupon_usages)
            if ($couponModel) {
                $this->couponService->recordUsage($couponModel, $order, Auth::user());
            }

            return $order;
        });
    }

    /**
     * Sinh mã đơn: ORD-YYYYMMDD-NNNN.
     * Đếm theo ngày — chấp nhận race condition thấp trong đồ án.
     * (Production: dùng lock/sequence table — note refactor sau.)
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';
        $todayCount = Order::whereDate('created_at', today())->count();

        return $prefix.str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);
    }
}
