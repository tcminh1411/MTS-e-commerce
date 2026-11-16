{{-- Mẫu giao diện danh sách sản phẩm phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Sản phẩm')

@section ('content')
<div class="space-y-6">
    <form method="GET" class="bg-white rounded-xl p-4 shadow-sm flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-[2_1_260px]">
            <label class="block text-sm font-medium mb-1" for="product-search">
                Từ khoá
            </label>
            <input id="product-search" name="q" value="{{ request('q') }}" placeholder="Tên sản phẩm..."
                class="w-full border rounded px-3 py-2 text-sm" />
        </div>

        <div class="min-w-[160px] flex-1">
            <label class="block text-sm font-medium mb-1" for="product-category">
                Danh mục
            </label>
            <select id="product-category" name="category" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">Tất cả</option>
                @foreach ($categories as $c)
                <option value="{{ $c->slug }}" @selected(request('category')===$c->slug)>
                    {{ $c->name }}
                </option>
                @foreach ($c->children as $child)
                <option value="{{ $child->slug }}" @selected(request('category')===$child->slug)>
                    - {{ $child->name }}
                </option>
                @endforeach
                @endforeach
            </select>
        </div>

        <div class="min-w-[150px] flex-1">
            <label class="block text-sm font-medium mb-1" for="product-brand">
                Thương hiệu
            </label>
            <select id="product-brand" name="brand" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">Tất cả</option>
                @foreach ($brands as $b)
                <option value="{{ $b->slug }}" @selected(request('brand')===$b->slug)>
                    {{ $b->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[210px] flex-[1.5_1_220px]">
            <label class="block text-sm font-medium mb-1">Giá (VND)</label>
            <div class="flex gap-2">
                <input type="number" name="min_price" aria-label="Giá từ" placeholder="Từ"
                    value="{{ request('min_price') }}" class="w-full min-w-0 border rounded px-2 py-2 text-sm" />
                <input type="number" name="max_price" aria-label="Giá đến" placeholder="Đến"
                    value="{{ request('max_price') }}" class="w-full min-w-0 border rounded px-2 py-2 text-sm" />
            </div>
        </div>

        <div class="min-w-[150px] flex-1">
            <label class="block text-sm font-medium mb-1" for="product-sort">
                Sắp xếp
            </label>
            <select id="product-sort" name="sort" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">Mới nhất</option>
                <option value="price_asc" @selected(request('sort')==='price_asc' )>
                    Giá tăng dần
                </option>
                <option value="price_desc" @selected(request('sort')==='price_desc' )>
                    Giá giảm dần
                </option>
                <option value="popular" @selected(request('sort')==='popular' )>
                    Bán chạy
                </option>
            </select>
        </div>

        <div class="flex gap-2">
            <button class="px-4 py-2 rounded bg-indigo-600 text-white text-sm">
                Áp dụng
            </button>
            <a href="{{ route('products.index') }}" class="px-4 py-2 rounded border text-sm">
                Reset
            </a>
        </div>
    </form>

    <div>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-bold">Sản phẩm</h1>
            <div class="text-sm text-gray-500">
                {{ $products->total() }} kết quả
            </div>
        </div>

        @if ($products->count())
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach ($products as $product)
            @include ('shop.products._card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
        @else
        <div class="bg-white rounded-xl p-10 text-center text-gray-500">
            Không tìm thấy sản phẩm phù hợp.
        </div>
        @endif
    </div>
</div>
@endsection