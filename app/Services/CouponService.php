<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;

/** Xử lý nghiệp vụ mã giảm giá dùng chung. */
class CouponService
{
    /**
     * Tìm coupon theo code, chỉ lấy coupon đang active (còn thời gian + còn lượt).
     */
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::active()
            ->where('code', strtoupper(trim($code)))
            ->first();
    }

    /**
     * Validate coupon + tính số tiền giảm.
     *
     * @return array{ok: bool, message: string, discount: int, coupon: ?Coupon}
     */
    public function apply(string $code, Cart $cart, ?User $user = null): array
    {
        $coupon = $this->findByCode($code);

        if (! $coupon) {
            return $this->fail('Mã giảm giá không tồn tại hoặc đã hết hạn.');
        }

        // 1. Check usage_limit toàn hệ thống
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $this->fail('Mã giảm giá đã hết lượt sử dụng.');
        }

        // 2. Check usage_limit_per_user (chỉ khi có user login)
        if ($user && $coupon->usage_limit_per_user !== null) {
            $userUsed = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userUsed >= $coupon->usage_limit_per_user) {
                return $this->fail('Bạn đã sử dụng mã này quá số lần cho phép.');
            }
        }

        // 3. Giỏ hàng phải có item
        $cart->loadMissing('items.product');
        $items = $cart->items;

        if ($items->isEmpty()) {
            return $this->fail('Giỏ hàng đang trống.');
        }

        // 4. Check min_order_amount trên TỔNG subtotal
        $fullSubtotal = $items->sum(fn ($i) => $i->unit_price * $i->quantity);

        if ($fullSubtotal < $coupon->min_order_amount) {
            return $this->fail(
                'Đơn hàng tối thiểu '.number_format($coupon->min_order_amount).'đ để dùng mã này.'
            );
        }

        // 5. Tính subtotal đủ điều kiện (eligible) — nếu coupon có condition
        $eligibleSubtotal = $this->calcEligibleSubtotal($coupon, $items);

        if ($eligibleSubtotal <= 0) {
            return $this->fail('Mã giảm giá không áp dụng được cho sản phẩm trong giỏ.');
        }

        // 6. Tính discount
        $discount = $coupon->calcDiscount($eligibleSubtotal);

        if ($discount <= 0) {
            return $this->fail('Mã giảm giá không mang lại giá trị giảm cho đơn này.');
        }

        return [
            'ok' => true,
            'message' => 'Áp dụng mã thành công, giảm '.number_format($discount).'đ.',
            'discount' => $discount,
            'coupon' => $coupon,
        ];
    }

    /**
     * Ghi nhận việc dùng coupon sau khi order được tạo thành công.
     * Tăng used_count + ghi 1 dòng vào coupon_usages.
     */
    public function recordUsage(Coupon $coupon, Order $order, ?User $user = null): void
    {
        $coupon->increment('used_count');

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user?->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);
    }

    /**
     * Tính subtotal của các item khớp điều kiện coupon.
     * Nếu coupon không có condition → trả về toàn bộ subtotal.
     */
    protected function calcEligibleSubtotal(Coupon $coupon, $items): int
    {
        if (! $coupon->hasConditions()) {
            return (int) $items->sum(fn ($i) => $i->unit_price * $i->quantity);
        }

        $conditions = $coupon->conditions;

        $categoryIds = $conditions->where('condition_type', 'category')->pluck('condition_id')->all();
        $productIds = $conditions->where('condition_type', 'product')->pluck('condition_id')->all();
        $brandIds = $conditions->where('condition_type', 'brand')->pluck('condition_id')->all();

        $eligible = $items->filter(function ($item) use ($categoryIds, $productIds, $brandIds) {
            $p = $item->product;
            if (! $p) {
                return false;
            }

            if (in_array($p->id, $productIds, true)) {
                return true;
            }
            if ($p->category_id && in_array($p->category_id, $categoryIds, true)) {
                return true;
            }
            if ($p->brand_id && in_array($p->brand_id, $brandIds, true)) {
                return true;
            }

            return false;
        });

        return (int) $eligible->sum(fn ($i) => $i->unit_price * $i->quantity);
    }

    /**
     * Helper trả về lỗi theo format thống nhất.
     */
    protected function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'discount' => 0,
            'coupon' => null,
        ];
    }
}
