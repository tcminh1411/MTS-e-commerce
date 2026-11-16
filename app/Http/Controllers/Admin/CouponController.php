<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Xử lý các yêu cầu liên quan đến mã giảm giá. */
class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query()->withCount('usages')->latest('id');

        // Filter theo trạng thái
        $status = $request->input('status');
        $now = now();

        if ($status === 'active') {
            $query->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'expired') {
            $query->where('ends_at', '<', $now);
        } elseif ($status === 'upcoming') {
            $query->where('starts_at', '>', $now);
        }

        // Tìm theo code/name
        if ($request->filled('q')) {
            $kw = trim($request->input('q'));
            $query->where(fn ($sub) => $sub
                ->where('code', 'like', "%{$kw}%")
                ->orWhere('name', 'like', "%{$kw}%"));
        }

        $coupons = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => Coupon::count(),
            'active' => Coupon::active()->count(),
            'inactive' => Coupon::where('is_active', false)->count(),
        ];

        return view('admin.coupons.index', compact('coupons', 'counts', 'status'));
    }

    public function create()
    {
        $coupon = new Coupon([
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'is_active' => true,
            'starts_at' => now(),
        ]);

        return view('admin.coupons.create', [
            'coupon' => $coupon,
            ...$this->conditionFormData(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $conditions = $this->extractConditions($data);
        $data['code'] = strtoupper(trim($data['code']));
        $data['used_count'] = 0;
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($data, $conditions): void {
            $coupon = Coupon::create($data);
            $this->syncConditions($coupon, $conditions);
        });

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Đã thêm mã giảm giá "'.$data['code'].'".');
    }

    public function edit(Coupon $coupon)
    {
        $coupon->load('conditions');

        return view('admin.coupons.edit', [
            'coupon' => $coupon,
            ...$this->conditionFormData($coupon),
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateData($request, $coupon->id);
        $conditions = $this->extractConditions($data);
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        unset($data['used_count']); // không cho sửa tay

        DB::transaction(function () use ($coupon, $data, $conditions): void {
            $coupon->update($data);
            $this->syncConditions($coupon, $conditions);
        });

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Đã cập nhật mã "'.$coupon->code.'".');
    }

    public function destroy(Coupon $coupon)
    {
        if ($coupon->usages()->exists()) {
            return back()->with('error',
                'Không thể xoá: mã đã có '.$coupon->usages()->count().' lượt dùng. Hãy tắt thay vì xoá để giữ lịch sử.');
        }

        $code = $coupon->code;
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Đã xoá mã "'.$code.'".');
    }

    /**
     * Trang xem lượt dùng đơn giản: user + đơn + ngày + số tiền đơn.
     */
    public function usages(Coupon $coupon)
    {
        $usages = $coupon->usages()
            ->with([
                'user:id,full_name,email',
                'order:id,order_number,total,status,placed_at',
            ])
            ->latest('used_at')
            ->paginate(20);

        return view('admin.coupons.usages', compact('coupon', 'usages'));
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/i',
                Rule::unique('coupons', 'code')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'integer', 'min:1'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'limit_conditions' => ['nullable', 'boolean'],
            'condition_categories' => ['nullable', 'array'],
            'condition_categories.*' => ['integer', 'distinct', 'exists:categories,id'],
            'condition_brands' => ['nullable', 'array'],
            'condition_brands.*' => ['integer', 'distinct', 'exists:brands,id'],
            'condition_products' => ['nullable', 'array'],
            'condition_products.*' => ['integer', 'distinct', 'exists:products,id'],
        ], [
            'code.required' => 'Vui lòng nhập mã.',
            'code.regex' => 'Mã chỉ gồm chữ cái, số và dấu gạch dưới.',
            'code.unique' => 'Mã này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên.',
            'type.required' => 'Vui lòng chọn loại giảm giá.',
            'value.required' => 'Vui lòng nhập giá trị.',
            'value.min' => 'Giá trị phải lớn hơn 0.',
            'ends_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'condition_categories.*.exists' => 'Danh mục được chọn không tồn tại.',
            'condition_brands.*.exists' => 'Thương hiệu được chọn không tồn tại.',
            'condition_products.*.exists' => 'Sản phẩm được chọn không tồn tại.',
        ]);

        if (
            $request->boolean('limit_conditions')
            && empty($data['condition_categories'])
            && empty($data['condition_brands'])
            && empty($data['condition_products'])
        ) {
            throw ValidationException::withMessages([
                'condition_categories' => 'Vui lòng chọn ít nhất một điều kiện áp dụng.',
            ]);
        }

        // percent: bắt buộc max_discount_amount, value 1..100
        if ($data['type'] === 'percent') {
            if (empty($data['max_discount_amount'])) {
                throw ValidationException::withMessages([
                    'max_discount_amount' => 'Vui lòng nhập mức giảm tối đa cho loại phần trăm.',
                ]);
            }
            if ((int) $data['value'] > 100) {
                throw ValidationException::withMessages([
                    'value' => 'Với loại phần trăm, giá trị phải từ 1 đến 100.',
                ]);
            }
        } else {
            // fixed: bỏ max_discount_amount
            $data['max_discount_amount'] = null;
        }

        $data['min_order_amount'] = (int) ($data['min_order_amount'] ?? 0);

        return $data;
    }

    /**
     * @return array{
     *     categories: Collection<int, Category>,
     *     brands: Collection<int, Brand>,
     *     products: Collection<int, Product>,
     *     selectedCategories: array<int, int>,
     *     selectedBrands: array<int, int>,
     *     selectedProducts: array<int, int>,
     *     hasConditions: bool
     * }
     */
    private function conditionFormData(?Coupon $coupon = null): array
    {
        $conditions = $coupon?->conditions ?? collect();
        $selectedCategories = $conditions
            ->where('condition_type', 'category')
            ->pluck('condition_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $selectedBrands = $conditions
            ->where('condition_type', 'brand')
            ->pluck('condition_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $selectedProducts = $conditions
            ->where('condition_type', 'product')
            ->pluck('condition_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $categories = Category::withTrashed()
            ->orderBy('name')
            ->get(['id', 'name']);
        $brands = Brand::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $products = Product::query()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);
        $additionalSelectedProducts = Product::withTrashed()
            ->whereIn('id', $selectedProducts)
            ->whereNotIn('id', $products->modelKeys())
            ->get(['id', 'name']);
        $products = $products->concat($additionalSelectedProducts)
            ->sortBy('name')
            ->values();

        $hasConditions = $selectedCategories !== []
            || $selectedBrands !== []
            || $selectedProducts !== [];

        return compact(
            'categories',
            'brands',
            'products',
            'selectedCategories',
            'selectedBrands',
            'selectedProducts',
            'hasConditions',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, int>>
     */
    private function extractConditions(array &$data): array
    {
        $limitConditions = $data['limit_conditions'] ?? false;
        unset($data['limit_conditions']);

        $conditions = [
            'category' => $limitConditions ? array_map('intval', $data['condition_categories'] ?? []) : [],
            'brand' => $limitConditions ? array_map('intval', $data['condition_brands'] ?? []) : [],
            'product' => $limitConditions ? array_map('intval', $data['condition_products'] ?? []) : [],
        ];

        unset(
            $data['condition_categories'],
            $data['condition_brands'],
            $data['condition_products'],
        );

        return $conditions;
    }

    /**
     * @param  array<string, array<int, int>>  $conditions
     */
    private function syncConditions(Coupon $coupon, array $conditions): void
    {
        $coupon->conditions()->delete();

        $newConditions = [];

        foreach ($conditions as $conditionType => $conditionIds) {
            foreach ($conditionIds as $conditionId) {
                $newConditions[] = [
                    'condition_type' => $conditionType,
                    'condition_id' => $conditionId,
                ];
            }
        }

        if ($newConditions !== []) {
            $coupon->conditions()->createMany($newConditions);
        }
    }
}
