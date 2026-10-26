{{-- Mẫu giao diện danh sách sản phẩm phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Sản phẩm')

@section ('content')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <aside class="md:col-span-1">
            <form
                method="GET"
                class="bg-white rounded-xl p-4 shadow-sm space-y-4 sticky top-24"
            >
                <div>
                    <label class="block text-sm font-medium mb-1"
                        >Từ khoá</label
                    >
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Tên sản phẩm..."
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                </div>

                <div>
                    <div class="text-sm font-medium mb-2">Danh mục</div>
                    <div class="space-y-1 text-sm max-h-56 overflow-auto">
                        @foreach ($categories as $c)
                            <label class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="category"
                                    value="{{ $c->slug }}"
                                    @checked (request('category')===$c->slug)
                                />
                                <span>{{ $c->name }}</span>
                            </label>
                            @foreach ($c->children as $child)
                                <label
                                    class="flex items-center gap-2 ml-4 text-gray-600"
                                >
                                    <input
                                        type="radio"
                                        name="category"
                                        value="{{ $child->slug }}"
                                        @checked (request('category')===$child->slug)
                                    />
                                    <span>{{ $child->name }}</span>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium mb-2">Thương hiệu</div>
                    <div class="space-y-1 text-sm max-h-40 overflow-auto">
                        @foreach ($brands as $b)
                            <label class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="brand"
                                    value="{{ $b->slug }}"
                                    @checked (request('brand')===$b->slug)
                                />
                                <span>{{ $b->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium mb-2">Giá (VND)</div>
                    <div class="flex gap-2">
                        <input
                            type="number"
                            name="min_price"
                            placeholder="Từ"
                            value="{{ request('min_price') }}"
                            class="w-full border rounded px-2 py-1.5 text-sm"
                        />
                        <input
                            type="number"
                            name="max_price"
                            placeholder="Đến"
                            value="{{ request('max_price') }}"
                            class="w-full border rounded px-2 py-1.5 text-sm"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1"
                        >Sắp xếp</label
                    >
                    <select
                        name="sort"
                        class="w-full border rounded px-3 py-2 text-sm"
                    >
                        <option value="">Mới nhất</option>
                        <option
                            value="price_asc"
                            @selected (request('sort')==='price_asc' )
                        >
                            Giá tăng dần
                        </option>
                        <option
                            value="price_desc"
                            @selected (request('sort')==='price_desc' )
                        >
                            Giá giảm dần
                        </option>
                        <option
                            value="popular"
                            @selected (request('sort')==='popular' )
                        >
                            Bán chạy
                        </option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button
                        class="flex-1 px-3 py-2 rounded bg-indigo-600 text-white text-sm"
                    >
                        Áp dụng
                    </button>
                    <a
                        href="{{ route('products.index') }}"
                        class="px-3 py-2 rounded border text-sm"
                        >Reset</a
                    >
                </div>
            </form>
        </aside>

        <div class="md:col-span-3">
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
