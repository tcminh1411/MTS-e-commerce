<?php

namespace App\Services;

use App\Mail\StockAvailableMail;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Xử lý nghiệp vụ tồn kho dùng chung. */
class InventoryService
{
    /**
     * Trừ kho khi chuyển đơn sang đang giao. Idempotent qua các biến động kho.
     */
    public function deduct(Order $order, int $warehouseId): void
    {
        $warehouse = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Kho đã chọn không còn hoạt động.',
            ]);
        }

        $activeWarehouseIds = $this->activeDeductionWarehouseIds($order);
        if ($activeWarehouseIds->count() === 1 && (int) $activeWarehouseIds->first() === $warehouse->id) {
            return;
        }
        if ($activeWarehouseIds->isNotEmpty()) {
            $this->restore($order);
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->variant_id) {
                continue;
            }

            $inventory = Inventory::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_variant_id', $item->variant_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = Inventory::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'product_variant_id' => $item->variant_id,
                    ],
                    ['quantity' => 0, 'low_stock_threshold' => 5]
                );
                $inventory = Inventory::query()
                    ->whereKey($inventory->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $stockBefore = (int) $inventory->quantity;
            $qty = (int) $item->quantity;

            if ($qty > $stockBefore) {
                throw ValidationException::withMessages([
                    'warehouse_id' => "Sản phẩm '{$item->product_name}' không đủ tồn kho tại kho đã chọn. "
                        ."Cần {$qty}, chỉ còn {$stockBefore}.",
                ]);
            }

            $stockAfter = $stockBefore - $qty;
            $inventory->update(['quantity' => $stockAfter]);

            $this->syncVariantStock($item->variant_id);

            StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $item->variant_id,
                'type' => 'out',
                'quantity' => -$qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'note' => "Trừ kho khi chuyển đơn {$order->order_number} sang đang giao",
                'created_by_user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Hoàn kho khi hủy đơn. Idempotent qua movement type='return'.
     */
    public function restore(Order $order): void
    {
        $movements = StockMovement::query()
            ->selectRaw('warehouse_id, product_variant_id, SUM(quantity) as quantity')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->whereIn('type', ['out', 'return'])
            ->groupBy('warehouse_id', 'product_variant_id')
            ->havingRaw('SUM(quantity) < 0')
            ->get();

        foreach ($movements as $movement) {
            if (! $movement->warehouse_id) {
                throw new RuntimeException('Không thể hoàn kho vì kho đã xuất sản phẩm không còn tồn tại.');
            }

            $inventory = Inventory::query()
                ->where('warehouse_id', $movement->warehouse_id)
                ->where('product_variant_id', $movement->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new RuntimeException('Không tìm thấy tồn kho của kho đã xuất sản phẩm.');
            }

            $stockBefore = (int) $inventory->quantity;
            $quantity = abs((int) $movement->quantity);
            $stockAfter = $stockBefore + $quantity;

            $inventory->update(['quantity' => $stockAfter]);
            $this->syncVariantStock((int) $movement->product_variant_id);

            StockMovement::create([
                'warehouse_id' => $movement->warehouse_id,
                'product_variant_id' => $movement->product_variant_id,
                'type' => 'return',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'note' => "Hoàn kho do hủy đơn {$order->order_number}",
                'created_by_user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Recompute variant.stock = SUM(inventories.quantity).
     * Nếu stock chuyển từ 0 → >0, gửi email cho các subscriber (sau khi transaction commit).
     */
    private function syncVariantStock(int $variantId): void
    {
        $variant = ProductVariant::find($variantId);
        $stockBefore = $variant ? (int) $variant->stock : 0;

        $sum = (int) Inventory::where('product_variant_id', $variantId)->sum('quantity');
        ProductVariant::where('id', $variantId)->update(['stock' => $sum]);

        // 0 → >0: có hàng trở lại → gửi mail alert
        if ($stockBefore === 0 && $sum > 0) {
            // Chạy sau khi transaction commit để mail fail không rollback inventory
            DB::afterCommit(function () use ($variantId) {
                $this->notifyStockAlerts($variantId);
            });
        }
    }

    /**
     * Gửi mail cho các subscriber của variant này (hoặc của product, không chỉ định variant).
     * Chỉ gửi cho những subscriber chưa được notify (notified_at IS NULL).
     */
    public function notifyStockAlerts(int $variantId): void
    {
        $variant = ProductVariant::with('product')->find($variantId);
        if (! $variant || ! $variant->product) {
            return;
        }

        $alerts = StockAlert::query()
            ->whereNull('notified_at')
            ->where(function ($q) use ($variant) {
                $q->where('variant_id', $variant->id)
                    ->orWhere(function ($sub) use ($variant) {
                        $sub->whereNull('variant_id')
                            ->where('product_id', $variant->product_id);
                    });
            })
            ->get();

        foreach ($alerts as $alert) {
            try {
                Mail::to($alert->email)->send(new StockAvailableMail($variant));
                $alert->update(['notified_at' => now()]);
            } catch (\Throwable $e) {
                Log::error('Gửi mail stock alert thất bại', [
                    'alert_id' => $alert->id,
                    'email' => $alert->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function activeDeductionWarehouseIds(Order $order): Collection
    {
        return StockMovement::query()
            ->selectRaw('warehouse_id, product_variant_id')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->whereIn('type', ['out', 'return'])
            ->groupBy('warehouse_id', 'product_variant_id')
            ->havingRaw('SUM(quantity) < 0')
            ->pluck('warehouse_id')
            ->unique()
            ->values();
    }
}
