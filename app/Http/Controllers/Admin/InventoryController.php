<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;

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
            ->whereHas('variant.product')
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
            ->whereHas('product')
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
    public function store(Request $request, InventoryService $inventoryService)
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

        $inventoryService->adjust(
            (int) $data['warehouse_id'],
            (int) $data['product_variant_id'],
            $data['type'],
            (int) $data['quantity'],
            $data['note'] ?? null,
        );

        return redirect()
            ->route('admin.inventories.index', ['warehouse_id' => $data['warehouse_id']])
            ->with('success', 'Đã cập nhật tồn kho.');
    }
}
