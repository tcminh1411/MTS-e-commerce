{{-- Mẫu giao diện danh sách câu hỏi thường gặp phía cửa hàng. --}}
@extends ('layouts.storefront')

@section ('title', 'Câu hỏi thường gặp')

@section ('content')
    <div class="max-w-3xl mx-auto py-8 px-4">
        {{-- Hero --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">Câu hỏi thường gặp</h1>
            <p class="text-gray-500">Tìm câu trả lời cho các thắc mắc phổ biến về đơn hàng, thanh toán, vận chuyển...</p>
        </div>

        @php $categoryLabels = \App\Models\Faq::categories(); @endphp

        @if ($faqs->isEmpty())
            <div
                class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500"
            >
                Chưa có câu hỏi nào. Vui lòng quay lại sau.
            </div>
        @else
            <div class="space-y-6">
                @foreach ($faqs as $categoryKey => $items)
                    <div>
                        <h2
                            class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2"
                        >
                            <span
                                class="inline-block w-1.5 h-5 bg-indigo-600 rounded"
                            ></span>
                            {{ $categoryLabels[$categoryKey] ?? 'Khác' }}
                            <span class="text-xs font-normal text-gray-400"
                                >({{ $items->count() }})</span
                            >
                        </h2>

                        <div class="bg-white rounded-xl shadow-sm divide-y">
                            @foreach ($items as $faq)
                                <details class="group">
                                    <summary
                                        class="cursor-pointer px-5 py-4 flex items-center justify-between gap-3 hover:bg-gray-50 transition"
                                    >
                                        <span
                                            class="font-medium text-gray-900"
                                            >{{ $faq->question }}</span
                                        >
                                        <svg
                                            class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform flex-shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </summary>
                                    <div
                                        class="px-5 pb-5 pt-1 text-sm text-gray-700 leading-relaxed whitespace-pre-line"
                                    >
                                        {{ $faq->answer }}
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- CTA --}}
        <div
            class="mt-10 bg-indigo-50 border border-indigo-200 rounded-xl p-6 text-center"
        >
            <p class="text-sm text-gray-700 mb-3">Không tìm thấy câu trả lời bạn cần?</p>
            <a
                href="{{ route('products.index') }}"
                class="inline-block px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium"
            >
                Liên hệ hỗ trợ
            </a>
        </div>
    </div>
@endsection
