{{-- Mẫu giao diện danh sách yêu thích phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Sản phẩm yêu thích')

@section ('content')
    <h1 class="text-2xl font-bold mb-6">Sản phẩm yêu thích</h1>

    @if (session('info'))
        <div
            class="mb-4 px-4 py-3 rounded-lg bg-blue-100 text-blue-800 text-sm"
        >
            {{ session('info') }}
        </div>
    @endif

    @if ($items->isEmpty())
        <div class="bg-white rounded-xl p-10 text-center text-gray-500">
            Bạn chưa có sản phẩm yêu thích nào.
            <a
                href="{{ route('products.index') }}"
                class="text-indigo-600 hover:underline"
                >Khám phá ngay →</a
            >
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($items as $item)
                @if ($item->product)
                    <div class="relative">
                        @include ('shop.products._card', ['product' => $item->product])

                        <form
                            method="POST"
                            action="{{ route('wishlist.toggle', $item->product) }}"
                            class="absolute top-2 right-2"
                        >
                            @csrf
                            <button
                                type="submit"
                                title="Xoá khỏi yêu thích"
                                class="w-8 h-8 rounded-full bg-white shadow flex items-center justify-center text-red-500 hover:bg-red-50"
                            >
                                ✕
                            </button>
                        </form>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="mt-6">{{ $items->links() }}</div>
    @endif
@endsection
