<?php

namespace Tests\Feature\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_order_creates_order_items_and_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);
        $variant = $this->makeVariant($product, price: 100000);
        $this->seedCart($user, $product, $variant, qty: 2);
        $rate = $this->makeShippingRate(fee: 30000);

        $order = app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'recipient_email' => 'k@example.com',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ]);

        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(200000, $order->subtotal);
        $this->assertSame(30000, $order->shipping_fee);
        $this->assertSame(230000, $order->total);
        $this->assertSame($user->id, $order->user_id);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(100000, $order->items->first()->unit_price);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'pending',
        ]);
    }

    public function test_place_order_applies_coupon_and_records_usage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 500000);
        $variant = $this->makeVariant($product, price: 500000);
        $this->seedCart($user, $product, $variant, qty: 1);
        $rate = $this->makeShippingRate(fee: 0);
        Coupon::create([
            'code' => 'FIX50',
            'name' => 'Fix 50k',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 0,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $order = app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
            'coupon_code' => 'FIX50',
        ]);

        $this->assertSame(50000, $order->discount_amount);
        $this->assertSame('FIX50', $order->coupon_code);
        $this->assertSame(450000, $order->total);
        $this->assertSame(1, Coupon::first()->fresh()->used_count);
        $this->assertDatabaseHas('coupon_usages', ['order_id' => $order->id]);
    }

    public function test_place_order_throws_when_cart_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        $rate = $this->makeShippingRate(fee: 0);

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ]);
    }

    public function test_place_order_throws_when_rate_unavailable_for_subtotal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);
        $variant = $this->makeVariant($product, price: 100000);
        $this->seedCart($user, $product, $variant, qty: 1);
        $rate = $this->makeShippingRate(fee: 0, minOrderValue: 500000);

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ]);
    }

    public function test_place_order_throws_when_coupon_invalid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);
        $variant = $this->makeVariant($product, price: 100000);
        $this->seedCart($user, $product, $variant, qty: 1);
        $rate = $this->makeShippingRate(fee: 0);

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
            'coupon_code' => 'NOT_EXIST',
        ]);
    }

    public function test_place_order_creates_guest_order_when_not_logged_in(): void
    {
        $this->withSession(['guest_cart_token' => 'guest-token']);
        $product = $this->makeProduct(price: 100000);
        $variant = $this->makeVariant($product, price: 100000);

        $cart = Cart::create(['session_token' => 'guest-token', 'currency' => 'VND']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
        ]);
        $rate = $this->makeShippingRate(fee: 0);

        $order = app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'recipient_email' => 'guest@example.com',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ]);

        $this->assertNull($order->user_id);
        $this->assertSame('guest@example.com', $order->guest_email);
    }

    public function test_order_number_format_is_ord_yyyymmdd_nnnn(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->seedCart($user, $product, $variant, qty: 1);
        $rate = $this->makeShippingRate(fee: 0);

        $order = app(OrderService::class)->placeOrder([
            'recipient_name' => 'Khách',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'HN',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ]);

        $this->assertMatchesRegularExpression(
            '/^ORD-\d{8}-\d{4}$/',
            $order->order_number
        );
    }

    private function makeProduct(int $price = 100000): Product
    {
        return Product::create([
            'name' => 'P'.Str::random(6),
            'slug' => 'p-'.Str::random(6),
            'status' => 'active',
            'price' => $price,
        ]);
    }

    private function makeVariant(Product $product, int $price = 100000): ProductVariant
    {
        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'name' => 'Default',
            'is_default' => true,
            'stock' => 10,
            'status' => 'active',
            'price' => $price,
        ]);
    }

    private function seedCart(User $user, Product $product, ProductVariant $variant, int $qty = 1): Cart
    {
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => $qty,
            'unit_price' => $variant->price,
        ]);

        return $cart;
    }

    private function makeShippingRate(int $fee = 0, ?int $minOrderValue = null): ShippingRate
    {
        $zone = ShippingZone::firstOrCreate(
            ['code' => 'TEST'],
            ['name' => 'Test', 'is_active' => true]
        );

        return ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => $fee,
            'is_active' => true,
            'min_order_value' => $minOrderValue,
        ]);
    }
}
