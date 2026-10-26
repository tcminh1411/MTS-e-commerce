<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Xử lý các yêu cầu liên quan đến tồn kho. */
class InventoryController extends Controller
{
    /**
     * Danh sách tồn kho — filter theo warehouse + search theo SKU/tên variant.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $keyword = trim((string) $request->input('q'));

        $warehouses = Warehouse::orderBy('id')->get();

        $inventories = Inventory::query()
            ->with(['warehouse', 'variant.product'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->whereHas('variant', function ($sub) use ($keyword) {
                    $sub->where('sku', 'like', "%{$keyword}%")
                        ->orWhereHas('product', function ($p) use ($keyword) {
                            $p->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderBy('warehouse_id')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventories.index', compact(
            'inventories', 'warehouses', 'warehouseId', 'keyword'
        ));
    }

    /**
     * Form nhập/xuất kho.
     */
    public function create(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('id')->get();

        // Chỉ load variant + product name, không load hết attribute (tránh N+1 nặng)
        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->orderBy('product_id')
            ->orderBy('id')
            ->get(['id', 'product_id', 'sku', 'is_default']);

        $selectedWarehouseId = $request->input('warehouse_id');

        return view('admin.inventories.create', compact(
            'warehouses', 'variants', 'selectedWarehouseId'
        ));
    }

    /**
     * Xử lý nhập/xuất — transaction: update inventory + variant.stock + ghi movement.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'type' => ['required', 'in:in,out,adjust'],
            'quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'warehouse_id.exists' => 'Kho không tồn tại.',
            'product_variant_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_variant_id.exists' => 'Sản phẩm không tồn tại.',
            'type.required' => 'Vui lòng chọn loại thao tác.',
            'type.in' => 'Loại thao tác không hợp lệ.',
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng phải là số nguyên.',
            'quantity.min' => 'Số lượng không được âm.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ]);

        DB::transaction(function () use ($data) {
            // 1. Lock hoặc tạo inventory row
            $inventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'product_variant_id' => $data['product_variant_id'],
                ],
                ['quantity' => 0, 'low_stock_threshold' => 5]
            );

            // Snapshot variant stock TRƯỚC khi update (để check 0→>0)
            $variantStockBefore = (int) (ProductVariant::find($data['product_variant_id'])->stock ?? 0);
            $stockBefore = (int) $inventory->quantity;
            $qty = (int) $data['quantity'];

            // 2. Tính stock_after theo type
            switch ($data['type']) {
                case 'in':
                    $stockAfter = $stockBefore + $qty;
                    break;
                case 'out':
                    if ($qty > $stockBefore) {
                        throw ValidationException::withMessages([
                            'quantity' => "Không thể xuất {$qty} — tồn kho hiện tại chỉ còn {$stockBefore}.",
                        ]);
                    }
                    $stockAfter = $stockBefore - $qty;
                    break;
                case 'adjust':
                default:
                    $stockAfter = $qty;
                    break;
            }

            // 3. Update inventory
            $inventory->update(['quantity' => $stockAfter]);

            // 4. Recompute variant.stock = SUM(inventories.quantity) của variant này
            $newVariantStock = (int) Inventory::where('product_variant_id', $data['product_variant_id'])
                ->sum('quantity');

            // Stock chuyển 0 → >0: gửi mail alert (sau commit)
            if ($variantStockBefore === 0 && $newVariantStock > 0) {
                DB::afterCommit(function () use ($data) {
                    app(InventoryService::class)
                        ->notifyStockAlerts((int) $data['product_variant_id']);
                });
            }
            ProductVariant::where('id', $data['product_variant_id'])
                ->update(['stock' => $newVariantStock]);

            // 5. Ghi stock_movement với delta
            StockMovement::create([
                'warehouse_id' => $data['warehouse_id'],
                'product_variant_id' => $data['product_variant_id'],
                'type' => $data['type'],
                'quantity' => $stockAfter - $stockBefore, // delta
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'manual',
                'reference_id' => null,
                'note' => $data['note'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.inventories.index', ['warehouse_id' => $data['warehouse_id']])
            ->with('success', 'Đã cập nhật tồn kho.');
    }
}
