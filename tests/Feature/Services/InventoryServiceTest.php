<?php

namespace Tests\Feature\Services;

use App\Mail\StockAvailableMail;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deduct_reduces_inventory_and_writes_movement(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 2);
        $warehouse = $this->makeWarehouse('WH-1');
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        app(InventoryService::class)->deduct($order, $warehouse->id);

        $this->assertSame(8, Inventory::first()->quantity);
        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_deduct_is_idempotent_for_same_warehouse(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 2);
        $warehouse = $this->makeWarehouse('WH-1');
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        $service = app(InventoryService::class);
        $service->deduct($order, $warehouse->id);
        $service->deduct($order, $warehouse->id);

        $this->assertSame(8, Inventory::first()->quantity);
        $this->assertSame(1, StockMovement::where('type', 'out')->count());
    }

    public function test_deduct_throws_when_stock_insufficient(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 5);
        $warehouse = $this->makeWarehouse('WH-1');
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->expectException(ValidationException::class);

        app(InventoryService::class)->deduct($order, $warehouse->id);
    }

    public function test_deduct_moves_stock_between_warehouses(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 2);
        $old = $this->makeWarehouse('OLD');
        $new = $this->makeWarehouse('NEW');
        Inventory::create([
            'warehouse_id' => $old->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        Inventory::create([
            'warehouse_id' => $new->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);
        StockMovement::create([
            'warehouse_id' => $old->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'stock_before' => 5,
            'stock_after' => 3,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        app(InventoryService::class)->deduct($order, $new->id);

        $this->assertSame(5, Inventory::where('warehouse_id', $old->id)->first()->quantity);
        $this->assertSame(8, Inventory::where('warehouse_id', $new->id)->first()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $old->id, 'type' => 'return', 'quantity' => 2,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $new->id, 'type' => 'out', 'quantity' => -2,
        ]);
    }

    public function test_restore_returns_stock_from_net_negative_movements(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 2);
        $warehouse = $this->makeWarehouse('WH-1');
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'stock_before' => 7,
            'stock_after' => 5,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        app(InventoryService::class)->restore($order);

        $this->assertSame(7, Inventory::first()->quantity);
        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'type' => 'return',
            'quantity' => 2,
        ]);
    }

    public function test_restore_is_idempotent(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 2);
        $warehouse = $this->makeWarehouse('WH-1');
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'stock_before' => 7,
            'stock_after' => 5,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $service = app(InventoryService::class);
        $service->restore($order);
        $service->restore($order);

        $this->assertSame(7, Inventory::first()->quantity);
        $this->assertSame(1, StockMovement::where('type', 'return')->count());
    }

    public function test_restore_does_nothing_when_order_never_shipped(): void
    {
        [$order, $variant] = $this->makeOrderWithVariant(qty: 2);
        $warehouse = $this->makeWarehouse('WH-1');
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        app(InventoryService::class)->restore($order);

        $this->assertSame(5, Inventory::first()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_notify_stock_alerts_sends_mail_and_marks_notified(): void
    {
        Mail::fake();
        $variant = $this->makeVariant(stock: 5);
        $alert = StockAlert::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'email' => 'buyer@example.com',
        ]);

        app(InventoryService::class)->notifyStockAlerts($variant->id);

        Mail::assertSent(StockAvailableMail::class, function ($mail) {
            return $mail->hasTo('buyer@example.com');
        });
        $this->assertNotNull($alert->fresh()->notified_at);
    }

    public function test_notify_stock_alerts_skips_already_notified(): void
    {
        Mail::fake();
        $variant = $this->makeVariant(stock: 5);
        StockAlert::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'email' => 'buyer@example.com',
            'notified_at' => now(),
        ]);

        app(InventoryService::class)->notifyStockAlerts($variant->id);

        Mail::assertNothingSent();
    }

    public function test_notify_stock_alerts_matches_product_level_alert(): void
    {
        Mail::fake();
        $variant = $this->makeVariant(stock: 5);
        StockAlert::create([
            'product_id' => $variant->product_id,
            'variant_id' => null,
            'email' => 'buyer@example.com',
        ]);

        app(InventoryService::class)->notifyStockAlerts($variant->id);

        Mail::assertSent(StockAvailableMail::class);
    }

    private function makeWarehouse(string $code): Warehouse
    {
        return Warehouse::create([
            'name' => $code,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function makeOrderWithVariant(int $qty): array
    {
        $product = Product::create([
            'name' => 'Test',
            'slug' => 'test-'.Str::random(6),
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
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => 1000 * $qty,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 1000 * $qty,
            'shipping_rate_name' => 'Std',
            'recipient_name' => 'X',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'X',
            'placed_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => 'Default',
            'quantity' => $qty,
            'unit_price' => 1000,
            'line_total' => 1000 * $qty,
        ]);

        return [$order->load('items'), $variant];
    }

    private function makeVariant(int $stock = 0): ProductVariant
    {
        $product = Product::create([
            'name' => 'Test',
            'slug' => 'test-'.Str::random(6),
            'status' => 'active',
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'name' => 'Default',
            'is_default' => true,
            'stock' => $stock,
            'status' => 'active',
        ]);
    }
}
