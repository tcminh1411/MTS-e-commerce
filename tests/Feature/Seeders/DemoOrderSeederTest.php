<?php

namespace Tests\Feature\Seeders;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\StockMovement;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoPersonalDataSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\DemoReturnRequestSeeder;
use Database\Seeders\DemoStockSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShippingSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoOrderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_shipped_orders_deduct_inventory_and_refunded_returns_restore_it(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            AttributeSeeder::class,
            ShippingSeeder::class,
            WarehouseSeeder::class,
            UserSeeder::class,
            DemoUserSeeder::class,
            DemoCatalogSeeder::class,
            DemoProductSeeder::class,
            DemoStockSeeder::class,
        ]);
        $initialInventory = Inventory::query()
            ->get()
            ->mapWithKeys(fn (Inventory $inventory): array => [
                $inventory->warehouse_id.':'.$inventory->product_variant_id => $inventory->quantity,
            ]);

        $this->seed(DemoOrderSeeder::class);
        $this->seed(DemoReturnRequestSeeder::class);
        $this->seed(DemoPersonalDataSeeder::class);

        $shippedOrders = Order::query()
            ->whereIn('status', ['shipping', 'completed'])
            ->with('items')
            ->get();

        $this->assertGreaterThan(0, $shippedOrders->count());

        foreach ($shippedOrders as $order) {
            foreach ($order->items as $item) {
                $this->assertDatabaseHas('stock_movements', [
                    'product_variant_id' => $item->variant_id,
                    'type' => 'out',
                    'quantity' => -$item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                ]);
            }
        }

        $refundedRequests = ReturnRequest::query()
            ->where('status', 'refunded')
            ->with('order.items')
            ->get();

        $this->assertGreaterThan(0, $refundedRequests->count());

        foreach ($refundedRequests as $returnRequest) {
            foreach ($returnRequest->order->items as $item) {
                $this->assertDatabaseHas('stock_movements', [
                    'product_variant_id' => $item->variant_id,
                    'type' => 'return',
                    'quantity' => $item->quantity,
                    'reference_type' => 'return',
                    'reference_id' => $returnRequest->id,
                ]);
            }
        }

        foreach (Inventory::query()->with('variant')->get() as $inventory) {
            $movementQuantity = (int) StockMovement::query()
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('product_variant_id', $inventory->product_variant_id)
                ->sum('quantity');
            $initialQuantity = $initialInventory[$inventory->warehouse_id.':'.$inventory->product_variant_id];
            $expectedQuantity = $initialQuantity + $movementQuantity;

            $this->assertSame($expectedQuantity, $inventory->quantity);
            $this->assertSame(
                (int) Inventory::query()
                    ->where('product_variant_id', $inventory->product_variant_id)
                    ->sum('quantity'),
                $inventory->variant->fresh()->stock
            );
        }
    }
}
