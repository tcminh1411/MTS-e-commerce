{{-- Mẫu giao diện form câu hỏi thường gặp trong trang quản trị. --}}
@php
    $isEdit = $faq->exists;
    $action = $isEdit ? route('admin.faqs.update', $faq) : route('admin.faqs.store');
    $categories = \App\Models\Faq::categories();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($isEdit)
        @method ('PUT')
    @endif

    {{-- Câu hỏi --}}
    <div>
        <label class="block font-medium mb-1"
            >Câu hỏi <span class="text-red-500">*</span></label
        >
        <input
            type="text"
            name="question"
            value="{{ old('question', $faq->question) }}"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="VD: Làm sao để kiểm tra đơn hàng?"
        />
        @error ('question')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Danh mục --}}
    <div>
        <label class="block font-medium mb-1">Danh mục</label>
        <select
            name="category"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            <option value="">— Không chọn —</option>
            @foreach ($categories as $key => $label)
                <option
                    value="{{ $key }}"
                    @selected (old('category', $faq->category) === $key)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error ('category')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Câu trả lời --}}
    <div>
        <label class="block font-medium mb-1"
            >Câu trả lời <span class="text-red-500">*</span></label
        >
        <textarea
            name="answer"
            rows="8"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Nội dung trả lời..."
            >{{ old('answer', $faq->answer) }}</textarea
        >
        @error ('answer')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Sort + Active --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block font-medium mb-1">Thứ tự hiển thị</label>
            <input
                type="number"
                name="sort_order"
                min="0"
                max="65535"
                value="{{ old('sort_order', $faq->sort_order) }}"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <p class="text-xs text-gray-500 mt-1">Số nhỏ hiển thị trước.</p>
            @error ('sort_order')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex items-center pt-7">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0" />
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked (old('is_active', $faq->is_active ?? true))
                    class="rounded border-gray-300 text-indigo-600"
                />
                <span class="text-sm">Hiển thị trên storefront</span>
            </label>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 pt-3 border-t">
        <button
            type="submit"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium"
        >
            {{ $isEdit ? 'Cập nhật' : 'Thêm mới' }}
        </button>
        <a
            href="{{ route('admin.faqs.index') }}"
            class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg"
        >
            Hủy
        </a>
    </div>
</form>
