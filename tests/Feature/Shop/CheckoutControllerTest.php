<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\CartItem;
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
