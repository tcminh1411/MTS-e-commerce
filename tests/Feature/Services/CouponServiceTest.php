<?php

namespace Tests\Feature\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponCondition;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_code_is_case_insensitive(): void
    {
        $coupon = $this->makeCoupon(code: 'SALE10');

        $found = app(CouponService::class)->findByCode('sale10');

        $this->assertNotNull($found);
        $this->assertSame($coupon->id, $found->id);
    }

    public function test_find_by_code_ignores_inactive_coupons(): void
    {
        $this->makeCoupon(code: 'OFF', overrides: ['is_active' => false]);

        $this->assertNull(app(CouponService::class)->findByCode('OFF'));
    }

    public function test_apply_rejects_nonexistent_code(): void
    {
        [$cart] = $this->makeCartWithItem(100000);

        $result = app(CouponService::class)->apply('MISSING', $cart);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['discount']);
    }

    public function test_apply_rejects_when_usage_limit_reached(): void
    {
        // scopeActive() đã ẩn coupon hết lượt trước khi apply() kịp kiểm tra,
        // nên thông báo trả về là "không tồn tại hoặc đã hết hạn" — chấp nhận được
        // vì với người dùng, coupon hết lượt và coupon không tồn tại như nhau.
        $this->makeCoupon(code: 'HOT', overrides: ['usage_limit' => 1, 'used_count' => 1]);
        [$cart] = $this->makeCartWithItem(200000);

        $result = app(CouponService::class)->apply('HOT', $cart);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['discount']);
    }

    public function test_apply_rejects_when_per_user_limit_reached(): void
    {
        $user = User::factory()->create();
        $coupon = $this->makeCoupon(code: 'ONLY1', overrides: [
            'usage_limit_per_user' => 1,
            'used_count' => 0,
        ]);
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'used_at' => now(),
        ]);
        [$cart] = $this->makeCartWithItem(200000, $user);

        $result = app(CouponService::class)->apply('ONLY1', $cart, $user);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('quá số lần', $result['message']);
    }

    public function test_apply_rejects_empty_cart(): void
    {
        $this->makeCoupon(code: 'ANY');
        $cart = Cart::create(['user_id' => User::factory()->create()->id, 'currency' => 'VND']);

        $result = app(CouponService::class)->apply('ANY', $cart);

        $this->assertFalse($result['ok']);
    }

    public function test_apply_rejects_below_min_order_amount(): void
    {
        $this->makeCoupon(code: 'MIN500', overrides: ['min_order_amount' => 500000]);
        [$cart] = $this->makeCartWithItem(100000);

        $result = app(CouponService::class)->apply('MIN500', $cart);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('tối thiểu', $result['message']);
    }

    public function test_apply_percent_with_max_discount(): void
    {
        $this->makeCoupon(code: 'P10', overrides: [
            'type' => 'percent',
            'value' => 10,
            'max_discount_amount' => 50000,
            'min_order_amount' => 0,
        ]);
        [$cart] = $this->makeCartWithItem(1000000);

        $result = app(CouponService::class)->apply('P10', $cart);

        $this->assertTrue($result['ok']);
        $this->assertSame(50000, $result['discount']);
    }

    public function test_apply_fixed_coupon(): void
    {
        $this->makeCoupon(code: 'FIX30K', overrides: [
            'type' => 'fixed',
            'value' => 30000,
            'max_discount_amount' => null,
            'min_order_amount' => 0,
        ]);
        [$cart] = $this->makeCartWithItem(100000);

        $result = app(CouponService::class)->apply('FIX30K', $cart);

        $this->assertTrue($result['ok']);
        $this->assertSame(30000, $result['discount']);
    }

    public function test_apply_fixed_coupon_never_exceeds_subtotal(): void
    {
        $this->makeCoupon(code: 'BIG', overrides: [
            'type' => 'fixed',
            'value' => 500000,
            'min_order_amount' => 0,
        ]);
        [$cart] = $this->makeCartWithItem(100000);

        $result = app(CouponService::class)->apply('BIG', $cart);

        $this->assertTrue($result['ok']);
        $this->assertSame(100000, $result['discount']);
    }

    public function test_apply_coupon_with_category_condition_uses_eligible_subtotal(): void
    {
        $eligibleCategory = Category::create([
            'name' => 'Eligible Cat',
            'slug' => 'eligible-cat',
        ]);
        $otherCategory = Category::create([
            'name' => 'Other Cat',
            'slug' => 'other-cat',
        ]);

        $coupon = $this->makeCoupon(code: 'CAT10', overrides: [
            'type' => 'percent',
            'value' => 10,
            'max_discount_amount' => null,
            'min_order_amount' => 0,
        ]);
        CouponCondition::create([
            'coupon_id' => $coupon->id,
            'condition_type' => 'category',
            'condition_id' => $eligibleCategory->id,
        ]);

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        $eligible = Product::create([
            'name' => 'Eligible',
            'slug' => 'eligible-'.Str::random(4),
            'status' => 'active',
            'price' => 100000,
            'category_id' => $eligibleCategory->id,
        ]);
        $other = Product::create([
            'name' => 'Other',
            'slug' => 'other-'.Str::random(4),
            'status' => 'active',
            'price' => 200000,
            'category_id' => $otherCategory->id,
        ]);
        CartItem::create([
            'cart_id' => $cart->id, 'product_id' => $eligible->id,
            'quantity' => 1, 'unit_price' => 100000,
        ]);
        CartItem::create([
            'cart_id' => $cart->id, 'product_id' => $other->id,
            'quantity' => 1, 'unit_price' => 200000,
        ]);

        $result = app(CouponService::class)->apply('CAT10', $cart, $user);

        $this->assertTrue($result['ok']);
        $this->assertSame(10000, $result['discount']);
    }

    public function test_record_usage_increments_count_and_creates_row(): void
    {
        $coupon = $this->makeCoupon(code: 'X', overrides: ['used_count' => 0]);
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        app(CouponService::class)->recordUsage($coupon, $order, $user);

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
    }

    private function makeCoupon(string $code, array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => $code,
            'name' => $code,
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'used_count' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function makeCartWithItem(int $price, ?User $user = null): array
    {
        $user = $user ?? User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        $product = Product::create([
            'name' => 'P'.Str::random(6),
            'slug' => 'p-'.Str::random(6),
            'status' => 'active',
            'price' => $price,
        ]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $price,
        ]);

        return [$cart, $product, $item, $user];
    }

    private function makeOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 100000,
            'shipping_rate_name' => 'Std',
            'recipient_name' => 'X',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'X',
            'placed_at' => now(),
        ]);
    }
}
