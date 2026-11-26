<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
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

    public function test_buy_now_accepts_simple_product_without_variant(): void
    {
        $product = Product::create([
            'name' => 'Simple product',
            'slug' => 'simple-product',
            'price' => 2500,
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
            'variant_id' => null,
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

    public function test_buy_now_rejects_variant_belonging_to_another_product(): void
    {
        $product = Product::create([
            'name' => 'Product one',
            'slug' => 'product-one',
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

    public function test_checkout_accepts_quantity_above_stock_and_deducts_only_when_shipping(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create([
            'name' => 'Main warehouse',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
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
        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
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
        $variant->update(['price' => 1400]);
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
            ->assertSessionHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame(5600, $order->subtotal);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'variant_id' => $variant->id,
            'quantity' => 4,
            'unit_price' => 1400,
            'line_total' => 5600,
        ]);
        $this->assertSame(3, $inventory->fresh()->quantity);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->patch(route('admin.orders.updateStatus', $order), [
                'to_status' => 'shipping',
                'warehouse_id' => $warehouse->id,
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, $inventory->fresh()->quantity);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_checkout_creates_order_for_product_without_variants(): void
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
            ->assertSessionHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 2,
        ]);
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
            'stock' => 0,
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
