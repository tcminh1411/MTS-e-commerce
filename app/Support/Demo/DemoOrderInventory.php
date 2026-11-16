<?php

namespace App\Support\Demo;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use RuntimeException;

class DemoOrderInventory
{
    public function deduct(Order $order, InventoryService $inventoryService): void
    {
        if (! in_array($order->status, ['shipping', 'completed'], true)) {
            return;
        }

        $order->loadMissing('items');

        if ($order->items->isEmpty() || $order->items->contains(fn (OrderItem $item) => ! $item->variant_id)) {
            throw new RuntimeException("Đơn demo {$order->order_number} không có biến thể hợp lệ để trừ kho.");
        }

        $requiredQuantities = $order->items
            ->groupBy('variant_id')
            ->map(fn (Collection $items): int => (int) $items->sum('quantity'));

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $inventories = Inventory::query()
            ->whereIn('warehouse_id', $warehouses->modelKeys())
            ->whereIn('product_variant_id', $requiredQuantities->keys())
            ->get()
            ->groupBy('warehouse_id');

        $warehouse = $warehouses->first(function (Warehouse $warehouse) use ($inventories, $requiredQuantities): bool {
            $warehouseInventories = $inventories->get($warehouse->id, collect())->keyBy('product_variant_id');

            return $requiredQuantities->every(
                fn (int $quantity, int|string $variantId): bool => (int) ($warehouseInventories->get($variantId)?->quantity ?? 0) >= $quantity
            );
        });

        if (! $warehouse) {
            throw new RuntimeException("Không kho demo nào đủ tồn để giao đơn {$order->order_number}.");
        }

        $inventoryService->deduct($order, $warehouse->id);
    }
}
