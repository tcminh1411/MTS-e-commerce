<?php

namespace Tests\Feature\Shop;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_pending_unpaid_cod_order_and_restore_inventory(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);
        $warehouse = Warehouse::create([
            'name' => 'Main warehouse',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Test product',
            'slug' => 'test-product',
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'name' => 'Default',
            'is_default' => true,
            'stock' => 8,
            'status' => 'active',
        ]);
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 8,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => 'Test product',
            'variant_name' => 'Default',
            'quantity' => 2,
            'unit_price' => 1000,
            'line_total' => 2000,
        ]);
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'stock_before' => 10,
            'stock_after' => 8,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Hủy đơn hàng')
            ->assertSee(route('orders.cancel', $order));

        $this->actingAs($user)
            ->post(route('orders.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã hủy đơn hàng '.$order->order_number.'.');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertSame(10, $warehouse->inventories()->first()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'changed_by_user_id' => $user->id,
        ]);
    }

    #[DataProvider('ineligibleOrders')]
    public function test_customer_cannot_cancel_order_outside_cancellable_state(array $overrides): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, $overrides);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Hủy đơn hàng');

        $this->actingAs($user)
            ->post(route('orders.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Chỉ có thể hủy đơn COD đang chờ xử lý và chưa thanh toán.');

        $this->assertSame($overrides['status'] ?? 'pending', $order->fresh()->status);
        $this->assertDatabaseMissing('order_status_history', [
            'order_id' => $order->id,
            'to_status' => 'cancelled',
        ]);
    }

    public static function ineligibleOrders(): array
    {
        return [
            'confirmed order' => [['status' => 'confirmed']],
            'paid order' => [['payment_status' => 'paid']],
            'online payment order' => [['payment_method' => 'vnpay']],
        ];
    }

    public function test_customer_cannot_cancel_another_users_order(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post(route('orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_guest_can_cancel_order_saved_in_guest_order_session(): void
    {
        $order = $this->makeOrder();
        Warehouse::create([
            'name' => 'Main warehouse',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $this->withSession(['guest_order_ids' => [$order->id]])
            ->post(route('orders.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'changed_by_user_id' => null,
        ]);
    }

    private function makeOrder(?User $user = null, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(10)),
            'user_id' => $user?->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => 2000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 2000,
            'shipping_rate_name' => 'Standard',
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'placed_at' => now(),
        ], $overrides));
    }
}
