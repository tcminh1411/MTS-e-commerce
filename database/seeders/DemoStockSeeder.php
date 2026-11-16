<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

// Mục đích: chia tồn kho demo giữa ba kho và đồng bộ tồn tổng của biến thể.
// Phụ thuộc: WarehouseSeeder, DemoProductSeeder.
class DemoStockSeeder extends Seeder
{
    private const STOCK_RANGE = [50, 500];

    public function run(): void
    {
        $warehouses = Warehouse::whereIn('code', ['HN-01', 'HCM-01', 'CT-01'])->pluck('id', 'code');

        if ($warehouses->count() !== 3) {
            throw new RuntimeException('Chạy WarehouseSeeder trước DemoStockSeeder.');
        }

        // Tỷ lệ phân bổ stock cho 3 kho
        $distribution = [
            'HN-01' => 0.6,   // Kho chính 60%
            'HCM-01' => 0.3,   // 30%
            'CT-01' => 0.1,   // 10%
        ];

        $variants = ProductVariant::all();
        if ($variants->isEmpty()) {
            throw new RuntimeException('Chạy DemoProductSeeder trước DemoStockSeeder.');
        }

        $totalRows = 0;

        DB::transaction(function () use ($warehouses, $distribution, $variants, &$totalRows) {
            foreach ($variants as $variant) {
                $totalQty = random_int(...self::STOCK_RANGE);

                $allocated = 0;
                $codes = array_keys($distribution);
                $lastCode = end($codes);

                foreach ($distribution as $code => $ratio) {
                    $warehouseId = $warehouses[$code] ?? null;
                    if (! $warehouseId) {
                        continue;
                    }

                    // Kho cuối nhận phần còn lại để tránh mất mát do làm tròn
                    $qty = ($code === $lastCode)
                        ? $totalQty - $allocated
                        : (int) round($totalQty * $ratio);

                    Inventory::updateOrCreate(
                        [
                            'warehouse_id' => $warehouseId,
                            'product_variant_id' => $variant->id,
                        ],
                        [
                            'quantity' => $qty,
                            'low_stock_threshold' => 5,
                        ]
                    );

                    $allocated += $qty;
                    $totalRows++;
                }
            }

            // Sync variant.stock = SUM(inventories.quantity)
            foreach ($variants as $v) {
                $sum = (int) Inventory::where('product_variant_id', $v->id)->sum('quantity');
                ProductVariant::where('id', $v->id)->update(['stock' => $sum]);
            }
        });

        $this->command->info("✅ DemoStockSeeder: chia stock 60/30/10 cho {$totalRows} dòng inventory (".count($variants).' variants).');
    }
}
