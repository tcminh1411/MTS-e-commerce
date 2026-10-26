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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_keeps_stock_until_admin_selects_shipping_warehouse(): void
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
            'stock' => 5,
            'status' => 'active',
        ]);
        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
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
            ->assertSessionHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame(5, $inventory->fresh()->quantity);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }
}
