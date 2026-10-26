{{-- Khung chat tư vấn sản phẩm, dùng Alpine.js. --}}
{{-- Dùng: @include('partials.chatbot') ngay trước </body>. Cần Alpine.js (đã có trong app.js). --}}

<style>
    [x-cloak] {
        display: none !important;
    }

    .mts-chat {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 9999;
        font-family: 'Manrope', system-ui, sans-serif;
    }
    .mts-chat * {
        box-sizing: border-box;
    }

    /* Nút mở */
    .mts-fab {
        position: relative;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        border: 0;
        cursor: pointer;
        color: #fff;
        background: linear-gradient(135deg, #8fb09c, #6a8b77);
        box-shadow: 0 8px 24px rgba(106, 139, 119, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        transition:
            transform 0.2s,
            box-shadow 0.2s;
    }
    .mts-fab:hover {
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 12px 28px rgba(106, 139, 119, 0.55);
    }
    .mts-fab svg {
        width: 26px;
        height: 26px;
    }
    .mts-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 10px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
    }

    /* Khung chat */
    .mts-panel {
        position: absolute;
        right: 0;
        bottom: 74px;
        width: 380px;
        height: 560px;
        max-height: calc(100dvh - 110px);
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow:
            0 20px 60px rgba(0, 0, 0, 0.18),
            0 0 0 1px rgba(0, 0, 0, 0.05);
        transform-origin: bottom right;
    }

    .mts-head {
        padding: 14px 16px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
        background: linear-gradient(135deg, #7d9d8a, #5f806d);
    }
    .mts-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-family: 'Fraunces', serif;
        position: relative;
        flex-shrink: 0;
    }
    .mts-avatar::after {
        content: '';
        position: absolute;
        right: 0;
        bottom: 0;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        background: #4ade80;
        border: 2px solid #6a8b77;
    }
    .mts-title {
        font-weight: 700;
        font-size: 15px;
        line-height: 1.2;
    }
    .mts-sub {
        font-size: 12px;
        opacity: 0.85;
    }
    .mts-icon-btn {
        background: rgba(255, 255, 255, 0.15);
        border: 0;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }
    .mts-icon-btn:hover {
        background: rgba(255, 255, 255, 0.28);
    }
    .mts-icon-btn svg {
        width: 16px;
        height: 16px;
    }

    /* Tin nhắn */
    .mts-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #f6f7f6;
        display: flex;
        flex-direction: column;
        gap: 14px;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
    }
    .mts-body::-webkit-scrollbar {
        width: 6px;
    }
    .mts-body::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    .mts-row {
        display: flex;
        gap: 8px;
        max-width: 88%;
        animation: mtsIn 0.22s ease-out;
    }
    .mts-row.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }
    .mts-row.bot {
        align-self: flex-start;
    }
    .mts-mini {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #7d9d8a;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        align-self: flex-end;
    }
    .mts-col {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }
    .mts-row.user .mts-col {
        align-items: flex-end;
    }
    .mts-bubble {
        padding: 10px 14px;
        font-size: 14px;
        line-height: 1.55;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .mts-row.bot .mts-bubble {
        background: #fff;
        color: #1f2937;
        border-radius: 18px 18px 18px 5px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    }
    .mts-row.user .mts-bubble {
        background: linear-gradient(135deg, #7d9d8a, #688a76);
        color: #fff;
        border-radius: 18px 18px 5px 18px;
    }
    .mts-row.err .mts-bubble {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .mts-bubble a {
        color: inherit;
        text-decoration: underline;
    }
    .mts-bubble code {
        background: rgba(0, 0, 0, 0.07);
        padding: 1px 5px;
        border-radius: 4px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 12.5px;
    }
    .mts-bubble ul {
        margin: 4px 0;
        padding-left: 18px;
    }
    .mts-time {
        font-size: 11px;
        color: #9ca3af;
        padding: 0 4px;
    }
    .mts-retry {
        background: none;
        border: 0;
        color: #b91c1c;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: underline;
        padding: 0 4px;
    }

    /* Gợi ý nhanh */
    .mts-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding-left: 36px;
    }
    .mts-chip {
        background: #fff;
        border: 1px solid #d5e0d9;
        color: #4b6b58;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .mts-chip:hover {
        background: #7d9d8a;
        color: #fff;
        border-color: #7d9d8a;
    }

    /* Đang nhập */
    .mts-typing {
        display: flex;
        gap: 4px;
        padding: 13px 16px !important;
    }
    .mts-typing i {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #9ca3af;
        animation: mtsBounce 1.2s infinite;
    }
    .mts-typing i:nth-child(2) {
        animation-delay: 0.15s;
    }
    .mts-typing i:nth-child(3) {
        animation-delay: 0.3s;
    }

    /* Ô nhập */
    .mts-foot {
        padding: 12px;
        border-top: 1px solid #eef0ee;
        background: #fff;
    }
    .mts-inputwrap {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        background: #f3f4f3;
        border-radius: 22px;
        padding: 6px 6px 6px 16px;
        border: 1.5px solid transparent;
        transition:
            border-color 0.15s,
            background 0.15s;
    }
    .mts-inputwrap:focus-within {
        background: #fff;
        border-color: #7d9d8a;
    }
    .mts-input {
        flex: 1;
        border: 0;
        background: transparent;
        outline: none;
        resize: none;
        font: inherit;
        font-size: 14px;
        line-height: 1.45;
        max-height: 110px;
        padding: 7px 0;
        color: #1f2937;
    }
    .mts-send {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 0;
        background: #7d9d8a;
        color: #fff;
        cursor: pointer;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition:
            background 0.15s,
            transform 0.15s,
            opacity 0.15s;
    }
    .mts-send:hover:not(:disabled) {
        background: #688a76;
        transform: scale(1.06);
    }
    .mts-send:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .mts-send svg {
        width: 17px;
        height: 17px;
    }
    .mts-hint {
        text-align: center;
        font-size: 11px;
        color: #9ca3af;
        margin-top: 7px;
    }

    @keyframes mtsIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: none;
        }
    }
    @keyframes mtsBounce {
        0%,
        60%,
        100% {
            transform: translateY(0);
            opacity: 0.5;
        }
        30% {
            transform: translateY(-5px);
            opacity: 1;
        }
    }

    /* Mobile: toàn màn hình */
    @media (max-width: 480px) {
        .mts-chat {
            right: 16px;
            bottom: 16px;
        }
        .mts-panel {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100dvh;
            max-height: none;
            border-radius: 0;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .mts-row,
        .mts-typing i {
            animation: none;
        }
        .mts-body {
            scroll-behavior: auto;
        }
    }
</style>

<div
    class="mts-chat"
    x-data="mtsChatbot()"
    x-cloak
    @keydown.escape.window="open = false"
>
    <section
        class="mts-panel"
        x-show="open"
        role="dialog"
        aria-label="Trợ lý AI MTS"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <header class="mts-head">
            <div class="mts-avatar">M</div>
            <div style="flex: 1; min-width: 0">
                <div class="mts-title">Trợ lý AI MTS</div>
                <div class="mts-sub">Luôn sẵn sàng hỗ trợ bạn</div>
            </div>
            <button
                class="mts-icon-btn"
                @click="clearChat()"
                title="Cuộc trò chuyện mới"
                aria-label="Cuộc trò chuyện mới"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.6 15A8 8 0 0018.4 9M18.4 9L20 9M5.6 15L4 15" /></svg>
            </button>
            <button
                class="mts-icon-btn"
                @click="open = false"
                title="Đóng"
                aria-label="Đóng"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </header>

        <div class="mts-body" x-ref="chatbox" aria-live="polite">
            <template x-for="msg in messages" :key="msg.id">
                <div
                    class="mts-row"
                    :class="[
                        msg.role === 'user' ? 'user' : 'bot',
                        msg.error ? 'err' : '',
                    ]"
                >
                    <div class="mts-mini" x-show="msg.role !== 'user'">M</div>
                    <div class="mts-col">
                        <div
                            class="mts-bubble"
                            x-html="render(msg.content)"
                        ></div>
                        <div style="display: flex; align-items: center">
                            <span class="mts-time" x-text="msg.time"></span>
                            <button
                                class="mts-retry"
                                x-show="msg.error"
                                @click="retry()"
                            >
                                Thử lại
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Gợi ý nhanh: chỉ hiện khi mới bắt đầu --}}
            <div class="mts-chips" x-show="messages.length <= 1 && !loading">
                <template x-for="s in suggestions" :key="s">
                    <button
                        class="mts-chip"
                        @click="send(s)"
                        x-text="s"
                    ></button>
                </template>
            </div>

            <div class="mts-row bot" x-show="loading">
                <div class="mts-mini">M</div>
                <div class="mts-bubble mts-typing" aria-label="Đang trả lời">
                    <i></i><i></i><i></i>
                </div>
            </div>
        </div>

        <footer class="mts-foot">
            <form class="mts-inputwrap" @submit.prevent="send()">
                <textarea
                    class="mts-input"
                    rows="1"
                    x-ref="input"
                    x-model="input"
                    maxlength="1000"
                    placeholder="Nhập tin nhắn..."
                    aria-label="Nhập tin nhắn"
                    @input="autoResize()"
                    @keydown.enter="onEnter($event)"
                ></textarea>
                <button
                    class="mts-send"
                    type="submit"
                    :disabled="loading || !input.trim()"
                    aria-label="Gửi"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14M13 6l6 6-6 6" /></svg>
                </button>
            </form>
            <div class="mts-hint">Enter để gửi · Shift+Enter xuống dòng</div>
        </footer>
    </section>

    <button
        class="mts-fab"
        @click="toggle()"
        :aria-label="open ? 'Đóng chat' : 'Mở chat'"
        :aria-expanded="open"
    >
        <svg x-show="!open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
        <svg x-show="open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7" /></svg>
        <span
            class="mts-badge"
            x-show="unread > 0 && !open"
            x-text="unread"
        ></span>
    </button>
</div>

<script>
    function mtsChatbot() {
        const STORE = 'mts_chat_v1';
        const WELCOME =
            'Chào anh/chị! Em là trợ lý AI của MTS. Em có thể giúp gì cho anh/chị hôm nay ạ?';

        return {
            open: false,
            input: '',
            loading: false,
            unread: 0,
            lastText: '',
            seq: 0,
            messages: [],
            suggestions: [
                'Top sản phẩm bán chạy',
                'Shop có bao nhiêu sản phẩm?',
                'Gợi ý tai nghe dưới 5 triệu',
                'Sản phẩm nào đang giảm giá?',
            ],

            init() {
                try {
                    const saved = JSON.parse(localStorage.getItem(STORE) || 'null');
                    if (Array.isArray(saved) && saved.length) {
                        this.messages = saved;
                        this.seq = Math.max(...saved.map((m) => m.id)) + 1;
                    }
                } catch (e) {}
                if (!this.messages.length) this.push('assistant', WELCOME);
                this.$watch('open', (v) => {
                    if (v) {
                        this.unread = 0;
                        this.focusInput();
                        this.scrollToBottom(true);
                    }
                });
            },

            toggle() {
                this.open = !this.open;
            },

            now() {
                return new Date().toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit',
                });
            },

            push(role, content, error = false) {
                this.messages.push({
                    id: this.seq++,
                    role,
                    content,
                    time: this.now(),
                    error,
                });
                this.save();
                this.scrollToBottom();
            },

            save() {
                try {
                    localStorage.setItem(
                        STORE,
                        JSON.stringify(this.messages.slice(-50)),
                    );
                } catch (e) {}
            },

            clearChat() {
                if (this.loading) return;
                this.messages = [];
                try {
                    localStorage.removeItem(STORE);
                } catch (e) {}
                this.push('assistant', WELCOME);
            },

            onEnter(e) {
                // Không gửi khi đang gõ dấu tiếng Việt (IME) hoặc giữ Shift
                if (e.shiftKey || e.isComposing || e.keyCode === 229) return;
                e.preventDefault();
                this.send();
            },

            autoResize() {
                const el = this.$refs.input;
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 110) + 'px';
            },

            focusInput() {
                this.$nextTick(() => this.$refs.input && this.$refs.input.focus());
            },

            retry() {
                // bỏ tin lỗi cuối rồi gửi lại tin người dùng gần nhất
                const last = this.messages[this.messages.length - 1];
                if (last && last.error) this.messages.pop();
                const lastUser = this.messages[this.messages.length - 1];
                if (lastUser && lastUser.role === 'user') this.messages.pop();
                this.send(this.lastText);
            },

            async send(preset) {
                const text = (preset ?? this.input).trim();
                if (!text || this.loading) return;

                this.lastText = text;
                this.push('user', text);
                this.input = '';
                this.$nextTick(() => this.autoResize());
                this.loading = true;

                const ctrl = new AbortController();
                const timer = setTimeout(() => ctrl.abort(), 30000);

                try {
                    // Gửi kèm 10 tin gần nhất để bot hiểu ngữ cảnh (backend có thể bỏ qua)
                    const history = this.messages
                        .filter((m) => !m.error)
                        .slice(-11, -1)
                        .map((m) => ({ role: m.role, content: m.content }));

                    const res = await fetch('{{ route('chatbot.reply') }}', {
                        method: 'POST',
                        signal: ctrl.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]',
                            ).content,
                        },
                        body: JSON.stringify({ message: text, history }),
                    });

                    if (res.status === 419)
                        throw new Error(
                            'Phiên làm việc đã hết hạn, vui lòng tải lại trang.',
                        );
                    if (res.status === 429)
                        throw new Error(
                            'Bạn gửi hơi nhanh, vui lòng đợi vài giây rồi thử lại.',
                        );

                    const data = await res.json();
                    if (!data.ok)
                        throw new Error(
                            'Xin lỗi, em đang bận chút. Anh/chị thử lại sau nhé!',
                        );

                    this.push('assistant', data.reply);
                    if (!this.open) this.unread++;
                } catch (e) {
                    const msg =
                        e.name === 'AbortError'
                            ? 'Phản hồi quá lâu, vui lòng thử lại.'
                            : e.message && !/fetch|JSON|Unexpected/i.test(e.message)
                              ? e.message
                              : 'Lỗi kết nối mạng, vui lòng thử lại.';
                    this.push('assistant', msg, true);
                } finally {
                    clearTimeout(timer);
                    this.loading = false;
                    this.focusInput();
                }
            },

            // Escape HTML trước, rồi mới dựng markdown nhẹ → an toàn với x-html
            render(text) {
                let s = String(text ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                s = s
                    .replace(/`([^`\n]+)`/g, '<code>$1</code>')
                    .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>')
                    .replace(
                        /(https?:\/\/[^\s<]+[^\s<.,;:!?)])/g,
                        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                    );
                // danh sách gạch đầu dòng
                s = s.replace(/(?:^|\n)((?:[-•*] .+(?:\n|$))+)/g, (m, block) => {
                    const items = block
                        .trim()
                        .split('\n')
                        .map((l) => '<li>' + l.replace(/^[-•*] /, '') + '</li>')
                        .join('');
                    return '\n<ul>' + items + '</ul>';
                });
                return s
                    .replace(/\n/g, '<br>')
                    .replace(/<br>(<\/?ul>)/g, '$1')
                    .replace(/(<\/ul>)<br>/g, '$1');
            },

            scrollToBottom(instant = false) {
                this.$nextTick(() => {
                    const el = this.$refs.chatbox;
                    if (!el) return;
                    instant
                        ? (el.scrollTop = el.scrollHeight)
                        : el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                });
            },
        };
    }
</script>
