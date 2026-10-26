<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

/** Xử lý các yêu cầu liên quan đến kho hàng. */
class WarehouseController extends Controller
{
    /**
     * Danh sách kho + search theo name/code.
     */
    public function index(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $warehouses = Warehouse::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                });
            })
            ->withCount('inventories')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.warehouses.index', compact('warehouses', 'keyword'));
    }

    /**
     * Form thêm kho mới.
     */
    public function create()
    {
        return view('admin.warehouses.create');
    }

    /**
     * Lưu kho mới.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:warehouses,code'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên kho.',
            'name.max' => 'Tên kho tối đa 100 ký tự.',
            'code.required' => 'Vui lòng nhập mã kho.',
            'code.max' => 'Mã kho tối đa 30 ký tự.',
            'code.unique' => 'Mã kho đã tồn tại.',
            'phone.max' => 'Số điện thoại tối đa 30 ký tự.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Warehouse::create($data);

        return redirect()
            ->route('admin.warehouses.index')
            ->with('success', 'Đã thêm kho "'.$data['name'].'".');
    }

    /**
     * Form sửa kho.
     */
    public function edit(Warehouse $warehouse)
    {
        return view('admin.warehouses.edit', compact('warehouse'));
    }

    /**
     * Cập nhật kho.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:warehouses,code,'.$warehouse->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên kho.',
            'name.max' => 'Tên kho tối đa 100 ký tự.',
            'code.required' => 'Vui lòng nhập mã kho.',
            'code.max' => 'Mã kho tối đa 30 ký tự.',
            'code.unique' => 'Mã kho đã tồn tại.',
            'phone.max' => 'Số điện thoại tối đa 30 ký tự.',
        ]);

        $data['is_active'] = $request->boolean('is_active', false);

        $warehouse->update($data);

        return redirect()
            ->route('admin.warehouses.index')
            ->with('success', 'Đã cập nhật kho "'.$warehouse->name.'".');
    }

    /**
     * Xóa kho — chặn nếu còn inventory quantity != 0.
     */
    public function destroy(Warehouse $warehouse)
    {
        $hasStock = $warehouse->inventories()
            ->where('quantity', '!=', 0)
            ->exists();

        if ($hasStock) {
            return redirect()
                ->route('admin.warehouses.index')
                ->with('error', 'Không thể xóa kho "'.$warehouse->name.'" vì vẫn còn tồn kho. Vui lòng chuyển hoặc xuất hết hàng trước.');
        }

        $name = $warehouse->name;
        $warehouse->delete();

        return redirect()
            ->route('admin.warehouses.index')
            ->with('success', 'Đã xóa kho "'.$name.'".');
    }
}
