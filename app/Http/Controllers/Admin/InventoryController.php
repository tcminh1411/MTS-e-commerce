<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $keyword = trim((string) $request->input('q'));

        $warehouses = Warehouse::orderBy('id')->get();

        $inventories = Inventory::query()
            ->with(['warehouse', 'variant.product', 'variant.attributeValues'])
            ->whereHas('variant.product')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->whereHas('variant', function ($sub) use ($keyword) {
                    $sub->where('sku', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhereHas('product', function ($p) use ($keyword) {
                            $p->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderBy('warehouse_id')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventories.index', compact('inventories', 'warehouses', 'warehouseId', 'keyword'));
    }

    public function create(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('id')->get();

        $productsData = Product::with(['variants' => function ($q) {
            $q->where('status', 'active')->orderBy('id');
        }])->whereHas('variants')->orderBy('name')->get();

        // Ép kiểu mảng chuẩn chỉnh để JavaScript đọc không lỗi
        $products = $productsData->map(function ($product) {
            return [
                'id' => (string) $product->id,
                'name' => $product->name,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => (string) $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $selectedWarehouseId = $request->input('warehouse_id');

        return view('admin.inventories.create', compact('warehouses', 'products', 'selectedWarehouseId'));
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'type' => ['required', 'in:in,out,adjust'],
            'note' => ['nullable', 'string', 'max:500'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['required', 'exists:product_variants,id'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
        ], [
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'variants.required' => 'Vui lòng chọn ít nhất một biến thể.',
            'variants.*.quantity.required' => 'Vui lòng nhập số lượng cho biến thể đã chọn.',
        ]);

        DB::transaction(function () use ($data, $inventoryService) {
            foreach ($data['variants'] as $variant) {
                $inventoryService->adjust(
                    (int) $data['warehouse_id'],
                    (int) $variant['id'],
                    $data['type'],
                    (int) $variant['quantity'],
                    $data['note'] ?? null,
                );
            }
        });

        return redirect()
            ->route('admin.inventories.index', ['warehouse_id' => $data['warehouse_id']])
            ->with('success', 'Đã cập nhật tồn kho hàng loạt thành công.');
    }
}
