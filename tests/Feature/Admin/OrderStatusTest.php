<?php

namespace Tests\Feature\Admin;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_selects_warehouse_when_shipping_order_and_stock_is_deducted_there(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrderWithItem();
        $firstWarehouse = $this->makeWarehouse('WH-1');
        $selectedWarehouse = $this->makeWarehouse('WH-2');
        $this->makeInventory($firstWarehouse, $order->items->first()->variant, 5);
        $this->makeInventory($selectedWarehouse, $order->items->first()->variant, 10);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Kho xuất hàng')
            ->assertSee('WH-2');

        $this->actingAs($admin)
            ->patch(route('admin.orders.updateStatus', $order), [
                'to_status' => 'shipping',
                'warehouse_id' => $selectedWarehouse->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('shipping', $order->fresh()->status);
        $this->assertSame(5, $firstWarehouse->inventories()->first()->quantity);
        $this->assertSame(8, $selectedWarehouse->inventories()->first()->quantity);
        $this->assertSame(13, $order->items->first()->variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $selectedWarehouse->id,
            'product_variant_id' => $order->items->first()->variant_id,
            'type' => 'out',
            'quantity' => -2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_admin_moves_existing_order_reservation_to_the_selected_shipping_warehouse(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrderWithItem();
        $reservedWarehouse = $this->makeWarehouse('WH-OLD');
        $selectedWarehouse = $this->makeWarehouse('WH-NEW');
        $variant = $order->items->first()->variant;
        $this->makeInventory($reservedWarehouse, $variant, 3);
        $this->makeInventory($selectedWarehouse, $variant, 10);
        StockMovement::create([
            'warehouse_id' => $reservedWarehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'stock_before' => 5,
            'stock_after' => 3,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.updateStatus', $order), [
                'to_status' => 'shipping',
                'warehouse_id' => $selectedWarehouse->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $reservedWarehouse->inventories()->first()->quantity);
        $this->assertSame(8, $selectedWarehouse->inventories()->first()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $reservedWarehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $selectedWarehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_admin_cannot_ship_order_without_selecting_active_warehouse(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrderWithItem();

        $this->actingAs($admin)
            ->patch(route('admin.orders.updateStatus', $order), [
                'to_status' => 'shipping',
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'type' => 'out',
        ]);
    }

    public function test_insufficient_stock_keeps_order_pending_and_does_not_create_movement(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrderWithItem();
        $warehouse = $this->makeWarehouse('WH-LOW');
        $this->makeInventory($warehouse, $order->items->first()->variant, 1);

        $this->actingAs($admin)
            ->patch(route('admin.orders.updateStatus', $order), [
                'to_status' => 'shipping',
                'warehouse_id' => $warehouse->id,
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(1, $warehouse->inventories()->first()->quantity);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_cancelling_shipped_order_restores_stock_to_the_selected_warehouse(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrderWithItem();
        $warehouse = $this->makeWarehouse('WH-SHIP');
        $otherWarehouse = $this->makeWarehouse('WH-OTHER');
        $this->makeInventory($warehouse, $order->items->first()->variant, 5);
        $this->makeInventory($otherWarehouse, $order->items->first()->variant, 7);

        $this->actingAs($admin)->patch(route('admin.orders.updateStatus', $order), [
            'to_status' => 'shipping',
            'warehouse_id' => $warehouse->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($admin)->patch(route('admin.orders.updateStatus', $order), [
            'to_status' => 'cancelled',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(5, $warehouse->inventories()->first()->quantity);
        $this->assertSame(7, $otherWarehouse->inventories()->first()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $order->items->first()->variant_id,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_cancelling_unshipped_order_does_not_increase_stock(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrderWithItem();
        $warehouse = $this->makeWarehouse('WH-PENDING');
        $this->makeInventory($warehouse, $order->items->first()->variant, 5);

        $this->actingAs($admin)
            ->patch(route('admin.orders.updateStatus', $order), [
                'to_status' => 'cancelled',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $warehouse->inventories()->first()->quantity);
        $this->assertDatabaseMissing('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'type' => 'return',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeOrderWithItem(): Order
    {
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
            'stock' => 0,
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(12)),
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
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'quantity' => 2,
            'unit_price' => 1000,
            'line_total' => 2000,
        ]);

        return $order->load('items.variant');
    }

    private function makeWarehouse(string $code): Warehouse
    {
        return Warehouse::create([
            'name' => $code,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function makeInventory(Warehouse $warehouse, ProductVariant $variant, int $quantity): Inventory
    {
        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
        $variant->update(['stock' => $variant->fresh()->stock + $quantity]);

        return $inventory;
    }
}
