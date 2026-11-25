<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Quản lý thuộc tính sản phẩm và các giá trị thuộc tính. */
class AttributeController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q'));

        $attributes = Attribute::query()
            ->withCount(['values', 'productAttributes'])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attributes.index', compact('attributes', 'keyword'));
    }

    public function create(): View
    {
        return view('admin.attributes.create', [
            'attribute' => new Attribute(['type' => 'select']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $newValues = $data['new_values'] ?? [];
        unset($data['new_values']);

        $data['slug'] = $this->uniqueAttributeSlug(
            Str::slug(($data['slug'] ?? '') ?: $data['name'])
        );
        $data['sort_order'] ??= 0;

        $attribute = DB::transaction(function () use ($data, $newValues): Attribute {
            $attribute = Attribute::create($data);
            $this->createValues($attribute, $newValues);

            return $attribute;
        });

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Đã thêm thuộc tính.');
    }

    public function edit(Attribute $attribute): View
    {
        $attribute->load('values');

        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $data = $request->validate($this->rules($attribute));
        $values = $data['values'] ?? [];
        $newValues = $data['new_values'] ?? [];
        $deleteValueIds = array_map('intval', $data['delete_value_ids'] ?? []);
        unset($data['values'], $data['new_values'], $data['delete_value_ids']);

        $submittedValueIds = array_map('intval', array_keys($values));
        $ownedValueIds = $attribute->values()
            ->whereIn('id', $submittedValueIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (array_diff($submittedValueIds, $ownedValueIds)) {
            throw ValidationException::withMessages([
                'values' => 'Có giá trị không thuộc tính này.',
            ]);
        }

        foreach ($values as $id => $valueData) {
            if (in_array((int) $id, $deleteValueIds, true)) {
                continue;
            }

            if (trim((string) ($valueData['value'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'values.'.$id.'.value' => 'Vui lòng nhập giá trị hoặc chọn xoá.',
                ]);
            }
        }

        if ($deleteValueIds !== [] && DB::table('variant_attribute_values')
            ->whereIn('attribute_value_id', $deleteValueIds)
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'delete_value_ids' => 'Không thể xoá giá trị đang được dùng bởi biến thể.',
            ]);
        }

        $data['slug'] = $this->uniqueAttributeSlug(
            Str::slug(($data['slug'] ?? '') ?: $data['name']),
            $attribute->id
        );
        $data['sort_order'] ??= 0;

        DB::transaction(function () use ($attribute, $data, $values, $newValues, $deleteValueIds): void {
            $attribute->update($data);

            foreach ($values as $id => $valueData) {
                $value = $attribute->values()->find((int) $id);

                if ($value === null) {
                    continue;
                }

                $valueData['value'] = trim($valueData['value']);
                $valueData['slug'] = $this->uniqueValueSlug(
                    $attribute,
                    Str::slug($valueData['value']),
                    $value->id
                );
                $value->update($valueData);
            }

            if ($deleteValueIds !== []) {
                $attribute->values()->whereIn('id', $deleteValueIds)->delete();
            }

            $this->createValues($attribute, $newValues);
        });

        return redirect()->route('admin.attributes.edit', $attribute)
            ->with('success', 'Đã cập nhật thuộc tính.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        if ($attribute->productAttributes()->exists()
            || DB::table('variant_attribute_values')
                ->join('attribute_values', 'attribute_values.id', '=', 'variant_attribute_values.attribute_value_id')
                ->where('attribute_values.attribute_id', $attribute->id)
                ->exists()
        ) {
            return redirect()->route('admin.attributes.index')
                ->with('error', 'Không thể xoá thuộc tính đang được dùng bởi sản phẩm hoặc biến thể.');
        }

        $attribute->delete();

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Đã xoá thuộc tính.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Attribute $attribute = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('attributes', 'slug')->ignore($attribute?->id)],
            'type' => ['required', Rule::in(['select', 'color'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'values' => ['nullable', 'array'],
            'values.*' => ['array:value,color_code'],
            'values.*.value' => ['nullable', 'string', 'max:150'],
            'values.*.color_code' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'new_values' => ['nullable', 'array'],
            'new_values.*' => ['array:value,color_code'],
            'new_values.*.value' => ['nullable', 'string', 'max:150'],
            'new_values.*.color_code' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'delete_value_ids' => ['nullable', 'array'],
            'delete_value_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('attribute_values', 'id')->where('attribute_id', $attribute?->id),
            ],
        ];
    }

    /**
     * @param  array<int, array{value?: string|null, color_code?: string|null}>  $values
     */
    private function createValues(Attribute $attribute, array $values): void
    {
        foreach ($values as $valueData) {
            $value = trim((string) ($valueData['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            $attribute->values()->create([
                'value' => $value,
                'slug' => $this->uniqueValueSlug($attribute, Str::slug($value)),
                'color_code' => $valueData['color_code'] ?? null,
            ]);
        }
    }

    private function uniqueAttributeSlug(string $base, ?int $ignoreId = null): string
    {
        $base = $base !== '' ? $base : 'attribute';
        $slug = $base;
        $suffix = 1;

        while (Attribute::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    private function uniqueValueSlug(Attribute $attribute, string $base, ?int $ignoreId = null): string
    {
        $base = $base !== '' ? $base : 'value';
        $slug = $base;
        $suffix = 1;

        while ($attribute->values()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
