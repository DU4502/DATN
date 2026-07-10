@extends(auth()->user()->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Chat khách hàng')
@section('hide-topbar-search')

@section('content')
@php
    $viewer = auth()->user();
@endphp

<style>
    .admin-chat-page {
        height: calc(100vh - 190px);
        max-height: calc(100vh - 190px);
        min-height: 520px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .admin-chat-heading {
        flex: 0 0 auto;
    }

    .admin-chat-grid {
        flex: 1 1 auto;
        min-height: 0;
    }

    .admin-chat-grid > [class*="col-"] {
        min-height: 0;
        display: flex;
    }

    .admin-chat-panel {
        flex: 1 1 auto;
        min-height: 0;
        max-height: 100%;
    }

    .admin-chat-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .admin-chat-fixed {
        flex: 0 0 auto;
    }

    .admin-chat-bubble {
        max-width: 72%;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    @media (max-width: 991.98px) {
        .admin-chat-page {
            height: auto;
            max-height: none;
            min-height: 0;
            overflow: visible;
        }

        .admin-chat-panel {
            height: min(620px, calc(100vh - 140px));
        }
    }
</style>

<div class="p-6 admin-chat-page">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3 admin-chat-heading">
        <div>
            <h1 class="h4 fw-bold mb-1 text-dark">Chat khách hàng</h1>
            <p class="text-secondary mb-0 small">Xem và phản hồi các cuộc trò chuyện từ khách hàng.</p>
        </div>
        @if($viewer->isSuperAdmin())
            <span class="badge text-bg-success rounded-pill px-3 py-2">Super Admin</span>
        @endif
    </div>

    <div class="row g-4 admin-chat-grid">
        <div class="col-12 col-lg-4 col-xl-3">
            <div class="bg-white border rounded-3 shadow-sm overflow-hidden h-100 d-flex flex-column admin-chat-panel">
                <div class="p-3 border-bottom bg-light admin-chat-fixed">
                    <h2 class="h6 fw-bold mb-0 text-dark">Danh sách cuộc trò chuyện</h2>
                </div>
                <div class="admin-chat-scroll">
                    @forelse($conversations as $conv)
                        <a
                            href="{{ route('admin.chat.show', $conv) }}"
                            class="d-block p-3 border-bottom text-decoration-none {{ isset($conversation) && $conversation->id === $conv->id ? 'bg-primary-subtle' : 'bg-white' }}"
                        >
                            <div class="d-flex align-items-start gap-2">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 36px; height: 36px;">
                                    {{ mb_strtoupper(mb_substr($conv->user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <strong class="text-dark small text-truncate">{{ $conv->user->name }}</strong>
                                        @if($conv->latestMessage)
                                            <span class="text-secondary small flex-shrink-0">{{ $conv->latestMessage->created_at->format('H:i') }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                                        @if($conv->cskh)
                                            <span class="text-secondary small text-truncate">Phụ trách: {{ $conv->cskh->name }}</span>
                                        @else
                                            <span class="text-warning small text-truncate">Chưa có người phụ trách</span>
                                        @endif
                                        <span class="rounded-circle {{ $conv->status === 'open' ? 'bg-success' : 'bg-secondary' }} flex-shrink-0" style="width: 8px; height: 8px;"></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="p-4 text-secondary text-center small mb-0">Chưa có cuộc trò chuyện nào.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8 col-xl-9">
            <div
                class="bg-white border rounded-3 shadow-sm overflow-hidden h-100 d-flex flex-column admin-chat-panel"
                @if(isset($conversation))
                    x-data="adminChat({
                        conversationId: {{ $conversation->id }},
                        canReply: {{ ($canReply ?? false) ? 'true' : 'false' }},
                        userId: {{ $conversation->user_id }},
                        viewerId: {{ $viewer->id }},
                        messagesUrl: '{{ route('admin.chat.messages', $conversation) }}',
                        replyUrl: '{{ route('admin.chat.reply', $conversation) }}',
                        csrfToken: '{{ csrf_token() }}'
                    })"
                    x-init="init()"
                @endif
            >
                @if(isset($conversation))
                    <div class="p-3 border-bottom bg-light admin-chat-fixed">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px;">
                                    {{ mb_strtoupper(mb_substr($conversation->user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="h6 fw-bold mb-0 text-dark text-truncate">{{ $conversation->user->name }}</h3>
                                    <p class="text-secondary small mb-0 text-truncate">{{ $conversation->user->email }}</p>
                                    @if($conversation->cskh)
                                        <p class="text-secondary small mb-0">Phụ trách: {{ $conversation->cskh->name }}</p>
                                    @endif
                                </div>
                            </div>
                            @if($conversation->status === 'open' && ($viewer->isAdmin() || $viewer->id === $conversation->cskh_id || !$conversation->cskh_id))
                                <form action="{{ route('admin.chat.close', $conversation) }}" method="POST" onsubmit="return confirm('Đóng cuộc trò chuyện này?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Đóng</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div x-ref="messageList" class="admin-chat-scroll p-3">
                        <div class="d-flex flex-column gap-3">
                            @foreach($conversation->messages as $message)
                                @php
                                    $displaySender = $message->display_sender;
                                    $isCustomerMessage = $displaySender->id === $conversation->user_id;
                                @endphp

<style>
    .admin-chat-page {
        height: calc(100vh - 190px);
        max-height: calc(100vh - 190px);
        min-height: 520px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .admin-chat-heading {
        flex: 0 0 auto;
    }

    .admin-chat-grid {
        flex: 1 1 auto;
        min-height: 0;
    }

    .admin-chat-grid > [class*="col-"] {
        min-height: 0;
        display: flex;
    }

    .admin-chat-panel {
        flex: 1 1 auto;
        min-height: 0;
        max-height: 100%;
    }

    .admin-chat-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .admin-chat-fixed {
        flex: 0 0 auto;
    }

    .admin-chat-bubble {
        max-width: 72%;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    @media (max-width: 991.98px) {
        .admin-chat-page {
            height: auto;
            max-height: none;
            min-height: 0;
            overflow: visible;
        }

        .admin-chat-panel {
            height: min(620px, calc(100vh - 140px));
        }
    }
</style>
                                <div class="d-flex {{ $isCustomerMessage ? 'justify-content-start' : 'justify-content-end' }}" data-message-id="{{ $message->id }}">
                                    <div class="px-3 py-2 rounded-3 admin-chat-bubble {{ $isCustomerMessage ? 'bg-light text-dark' : 'bg-primary text-white' }}">
                                        @if($message->content)
                                            <p class="small mb-1">{{ $message->content }}</p>
                                        @endif
                                        <p class="mb-0 {{ $isCustomerMessage ? 'text-secondary' : 'text-white-50' }}" style="font-size: 0.72rem;">{{ $message->created_at->format('H:i') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($conversation->status === 'open')
                        <div class="p-3 border-top bg-light admin-chat-fixed">
                            <template x-if="canReply">
                                <div class="d-flex align-items-center gap-2">
                                    <input
                                        type="text"
                                        x-model="newMessage"
                                        @keydown.enter.prevent="sendMessage()"
                                        placeholder="Nhập tin nhắn..."
                                        class="form-control form-control-sm"
                                        :disabled="loading"
                                    >
                                    <button
                                        type="button"
                                        @click="sendMessage()"
                                        :disabled="loading || !newMessage.trim()"
                                        class="btn btn-sm btn-primary px-3"
                                    >
                                        <span x-show="!loading">Gửi</span>
                                        <span x-show="loading">Đang gửi...</span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!canReply">
                                <p class="text-secondary text-center small mb-0 py-1">Bạn không có quyền trả lời cuộc trò chuyện này.</p>
                            </template>
                        </div>
                    @else
                        <div class="p-3 border-top bg-light text-center text-secondary small admin-chat-fixed">Cuộc trò chuyện đã đóng.</div>
                    @endif
                @else
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center text-secondary" style="min-height: 520px;">
                        <div class="text-center">
                            <i class="bi bi-chat-dots display-5 d-block mb-3 text-secondary"></i>
                            <p class="mb-0">Chọn một cuộc trò chuyện để xem và phản hồi.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(isset($conversation))
<script>
function adminChat(config) {
    return {
        ...config,
        newMessage: '',
        loading: false,
        pollInterval: null,

        init() {
            this.scrollToBottom();
            this.pollInterval = setInterval(() => this.fetchMessages(), 3000);
        },

        async fetchMessages() {
            try {
                const res = await fetch(this.messagesUrl, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.success) return;

                const prevCount = this.$refs.messageList?.querySelectorAll('[data-message-id]').length ?? 0;
                if (data.messages.length !== prevCount) {
                    this.renderMessages(data.messages);
                }
            } catch (e) {
                console.error('Poll error', e);
            }
        },

        renderMessages(messages) {
            const container = this.$refs.messageList;
            if (!container) return;

            container.innerHTML = `<div class="d-flex flex-column gap-3">${messages.map((msg) => {
                const isCustomer = msg.sender_id === this.userId;
                const align = isCustomer ? 'justify-content-start' : 'justify-content-end';
                const bubble = isCustomer ? 'bg-light text-dark' : 'bg-primary text-white';
                const timeClass = isCustomer ? 'text-secondary' : 'text-white-50';
                const time = new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });

                return `<div class="d-flex ${align}" data-message-id="${msg.id}">
                    <div class="px-3 py-2 rounded-3 admin-chat-bubble ${bubble}">
                        <p class="small mb-1">${this.escapeHtml(msg.content || '')}</p>
                        <p class="mb-0 ${timeClass}" style="font-size: 0.72rem;">${time}</p>
                    </div>
                </div>`;
            }).join('')}</div>`;

            this.scrollToBottom();
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messageList;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        async sendMessage() {
            if (!this.newMessage.trim() || this.loading || !this.canReply) return;

            this.loading = true;
            const formData = new FormData();
            formData.append('content', this.newMessage);
            formData.append('_token', this.csrfToken);

            try {
                const res = await fetch(this.replyUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    this.newMessage = '';
                    await this.fetchMessages();
                } else {
                    alert(data.message || 'Không thể gửi tin nhắn.');
                }
            } catch (e) {
                alert('Lỗi kết nối.');
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endif
@endsection
