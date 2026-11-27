<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_now_checks_out_session_item_and_preserves_cart(): void
    {
        $user = User::factory()->create();
        $cartProduct = Product::create([
            'name' => 'Cart product',
            'slug' => 'cart-product',
            'price' => 9000,
            'status' => 'active',
        ]);
        $buyNowProduct = Product::create([
            'name' => 'Buy now product',
            'slug' => 'buy-now-product',
            'price' => 2000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $buyNowProduct->id,
            'sku' => 'BUY-NOW-001',
            'name' => 'Blue',
            'price' => 3000,
            'stock' => 5,
            'status' => 'active',
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'currency' => 'VND',
        ]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $cartProduct->id,
            'quantity' => 3,
            'unit_price' => 9000,
        ]);
        Coupon::create([
            'code' => 'BUY500',
            'name' => 'Buy now discount',
            'type' => 'fixed',
            'value' => 500,
            'min_order_amount' => 0,
            'used_count' => 0,
            'is_active' => true,
        ]);
        $zone = ShippingZone::create([
            'name' => 'Buy now zone',
            'code' => 'BUY-NOW',
            'is_active' => true,
        ]);
        $rate = ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.buy-now'), [
                'product_id' => $buyNowProduct->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('checkout.buy_now', [
                'product_id' => $buyNowProduct->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee($buyNowProduct->name)
            ->assertDontSee($cartProduct->name)
            ->assertViewHas('checkoutItems', function ($items) use ($buyNowProduct, $variant): bool {
                return $items->count() === 1
                    && $items->first()->product_id === $buyNowProduct->id
                    && $items->first()->variant_id === $variant->id
                    && $items->first()->quantity === 2;
            });

        $this->post(route('checkout.place'), [
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
            'coupon_code' => 'INVALID',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Mã giảm giá không tồn tại hoặc đã hết hạn.')
            ->assertSessionHas('checkout.buy_now', [
                'product_id' => $buyNowProduct->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $this->assertDatabaseCount('orders', 0);

        $this->post(route('checkout.place'), [
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
            'coupon_code' => 'BUY500',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('checkout.buy_now');

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame(6000, $order->subtotal);
        $this->assertSame(500, $order->discount_amount);
        $this->assertSame(5600, $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $buyNowProduct->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 3000,
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id,
            'product_id' => $cartProduct->id,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
            'unit_price' => 9000,
        ]);
        $this->assertSame(1, Coupon::query()->where('code', 'BUY500')->firstOrFail()->fresh()->used_count);
    }

    public function test_buy_now_accepts_simple_product_with_default_variant(): void
    {
        $product = Product::create([
            'name' => 'Simple product',
            'slug' => 'simple-product',
            'price' => 2500,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SIMPLE-PRODUCT-DEFAULT',
            'name' => 'Mặc định',
            'price' => 2500,
            'is_default' => true,
            'stock' => 5,
            'status' => 'active',
        ]);
        $zone = ShippingZone::create([
            'name' => 'Simple product zone',
            'code' => 'SIMPLE-PRODUCT',
            'is_active' => true,
        ]);
        $rate = ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => 0,
            'is_active' => true,
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('checkout.index'));

        $this->post(route('checkout.place'), [
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('checkout.buy_now');

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 2500,
        ]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_buy_now_rejects_missing_variant_without_creating_cart_item(): void
    {
        $product = Product::create([
            'name' => 'Variant product',
            'slug' => 'variant-product',
            'product_type' => 'variable',
            'price' => 2000,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-001',
            'name' => 'Red',
            'price' => 3000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
            ->assertSessionHasErrors([
                'variant_id' => 'Vui lòng chọn phân loại sản phẩm.',
            ])
            ->assertSessionMissing('checkout.buy_now');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_buy_now_rejects_out_of_stock_variant(): void
    {
        $product = Product::create([
            'name' => 'Out of stock buy now product',
            'slug' => 'out-of-stock-buy-now-product',
            'price' => 2000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'OUT-OF-STOCK-BUY-NOW',
            'name' => 'Blue',
            'price' => 3000,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])
            ->assertSessionHasErrors([
                'variant_id' => 'Phân loại này đã hết hàng.',
            ])
            ->assertSessionMissing('checkout.buy_now');
    }

    public function test_buy_now_rejects_quantity_above_variant_stock(): void
    {
        $product = Product::create([
            'name' => 'Low stock buy now product',
            'slug' => 'low-stock-buy-now-product',
            'price' => 2000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'LOW-STOCK-BUY-NOW',
            'name' => 'Blue',
            'price' => 3000,
            'stock' => 1,
            'status' => 'active',
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])
            ->assertSessionHasErrors([
                'variant_id' => 'Tồn kho không đủ cho số lượng đã chọn.',
            ])
            ->assertSessionMissing('checkout.buy_now');
    }

    public function test_buy_now_checkout_redirects_if_variant_sells_out_before_checkout_loads(): void
    {
        $product = Product::create([
            'name' => 'Buy now stock change product',
            'slug' => 'buy-now-stock-change-product',
            'price' => 2000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BUY-NOW-STOCK-CHANGE',
            'name' => 'Blue',
            'price' => 3000,
            'stock' => 1,
            'status' => 'active',
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('checkout.index'));

        $variant->update(['stock' => 0]);

        $this->get(route('checkout.index'))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'Sản phẩm hoặc phân loại không còn khả dụng.')
            ->assertSessionMissing('checkout.buy_now');
    }

    public function test_buy_now_order_is_rejected_if_variant_sells_out_after_checkout_loads(): void
    {
        $product = Product::create([
            'name' => 'Buy now final stock product',
            'slug' => 'buy-now-final-stock-product',
            'price' => 2000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BUY-NOW-FINAL-STOCK',
            'name' => 'Blue',
            'price' => 3000,
            'stock' => 1,
            'status' => 'active',
        ]);
        $zone = ShippingZone::create([
            'name' => 'Buy now stock zone',
            'code' => 'BUY-NOW-STOCK',
            'is_active' => true,
        ]);
        $rate = ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => 0,
            'is_active' => true,
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('checkout.index'));

        $variant->update(['stock' => 0]);

        $this->post(route('checkout.place'), [
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'shipping_rate_id' => $rate->id,
            'payment_method' => 'cod',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Phân loại này đã hết hàng.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_redirects_to_cart_when_variant_stock_is_insufficient(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Insufficient cart stock product',
            'slug' => 'insufficient-cart-stock-product',
            'price' => 2000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'INSUFFICIENT-CART-STOCK',
            'name' => 'Blue',
            'price' => 3000,
            'stock' => 1,
            'status' => 'active',
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'currency' => 'VND',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 3000,
        ]);

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'Giỏ hàng có sản phẩm hết hàng hoặc vượt tồn kho. Vui lòng cập nhật giỏ hàng.');
    }

    public function test_buy_now_rejects_variant_belonging_to_another_product(): void
    {
        $product = Product::create([
            'name' => 'Product one',
            'slug' => 'product-one',
            'product_type' => 'variable',
            'price' => 2000,
            'status' => 'active',
        ]);
        $otherProduct = Product::create([
            'name' => 'Product two',
            'slug' => 'product-two',
            'price' => 3000,
            'status' => 'active',
        ]);
        $foreignVariant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'sku' => 'FOREIGN-001',
            'name' => 'Green',
            'price' => 3000,
            'stock' => 5,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'LOCAL-001',
            'name' => 'Blue',
            'price' => 2000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $this->post(route('checkout.buy-now'), [
            'product_id' => $product->id,
            'variant_id' => $foreignVariant->id,
            'quantity' => 1,
        ])
            ->assertSessionHasErrors([
                'variant_id' => 'Phân loại không hợp lệ cho sản phẩm này.',
            ])
            ->assertSessionMissing('checkout.buy_now');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_rejects_cart_quantity_above_variant_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Test product',
            'slug' => 'test-product-'.Str::lower(Str::random(8)),
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'name' => 'Default',
            'is_default' => true,
            'stock' => 3,
            'status' => 'active',
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'currency' => 'VND',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 4,
            'unit_price' => 1000,
        ]);
        $zone = ShippingZone::create([
            'name' => 'Test zone',
            'code' => 'TEST',
            'is_active' => true,
        ]);
        $rate = ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.place'), [
                'recipient_name' => 'Test customer',
                'recipient_phone' => '0900000000',
                'shipping_address' => 'Test address',
                'shipping_rate_id' => $rate->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Tồn kho không đủ cho số lượng đã chọn.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 4,
        ]);
    }

    public function test_checkout_rejects_cart_product_without_variant_for_stock_tracking(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Variable product without variants',
            'slug' => 'variable-no-variants',
            'product_type' => 'variable',
            'price' => 2500,
            'status' => 'active',
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'currency' => 'VND',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 2500,
        ]);
        $zone = ShippingZone::create([
            'name' => 'Test zone',
            'code' => 'NO-VARIANT',
            'is_active' => true,
        ]);
        $rate = ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.place'), [
                'recipient_name' => 'Test customer',
                'recipient_phone' => '0900000000',
                'shipping_address' => 'Test address',
                'shipping_rate_id' => $rate->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Sản phẩm trong giỏ chưa có biến thể để kiểm tra tồn kho. Vui lòng xóa và thêm lại sản phẩm.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_numbers_increment_sequentially_for_same_day(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->setTime(10, 0));
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Sequence product',
            'slug' => 'sequence-product',
            'status' => 'active',
            'price' => 1000,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SEQUENCE-001',
            'name' => 'Default',
            'is_default' => true,
            'price' => 1000,
            'stock' => 2,
            'status' => 'active',
        ]);
        $cart = Cart::create([
            'user_id' => $user->id,
            'currency' => 'VND',
        ]);
        $zone = ShippingZone::create([
            'name' => 'Sequence zone',
            'code' => 'SEQUENCE',
            'is_active' => true,
        ]);
        $rate = ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'fee' => 0,
            'is_active' => true,
        ]);

        for ($index = 0; $index < 2; $index++) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
                'unit_price' => 1000,
            ]);

            $this->actingAs($user)
                ->post(route('checkout.place'), [
                    'recipient_name' => 'Test customer',
                    'recipient_phone' => '0900000000',
                    'shipping_address' => 'Test address',
                    'shipping_rate_id' => $rate->id,
                    'payment_method' => 'cod',
                ])
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-20261030-0001',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-20261030-0002',
        ]);
        $this->assertDatabaseHas('order_number_sequences', [
            'sequence_date' => '20261030',
            'last_number' => 2,
        ]);
    }
}
