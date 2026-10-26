<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoStockSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::whereIn('code', ['HN-01', 'HCM-01', 'CT-01'])->pluck('id', 'code');

        if ($warehouses->isEmpty()) {
            $this->command->error('❌ Chưa có kho — chạy WarehouseSeeder trước.');

            return;
        }

        // Tỷ lệ phân bổ stock cho 3 kho
        $distribution = [
            'HN-01' => 0.6,   // Kho chính 60%
            'HCM-01' => 0.3,   // 30%
            'CT-01' => 0.1,   // 10%
        ];

        $variants = ProductVariant::all();
        $totalRows = 0;

        DB::transaction(function () use ($warehouses, $distribution, $variants, &$totalRows) {
            foreach ($variants as $variant) {
                $totalQty = random_int(50, 500); // Tổng tồn của variant trên toàn hệ thống

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
