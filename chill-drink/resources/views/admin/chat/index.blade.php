@extends(auth()->user()->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Chat khách hàng')
@section('hide-topbar-search')

@section('content')
@php
    $viewer = auth()->user();
    $canReply = $canReply ?? false;
@endphp

{{-- ===== STYLES ===== --}}
<style>
    .admin-chat-page {
        height: calc(100vh - 190px);
        max-height: calc(100vh - 190px);
        min-height: 520px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .admin-chat-heading { flex: 0 0 auto; }
    .admin-chat-grid { flex: 1 1 auto; min-height: 0; }
    .admin-chat-grid > [class*="col-"] { min-height: 0; display: flex; }
    .admin-chat-panel { flex: 1 1 auto; min-height: 0; max-height: 100%; }
    .admin-chat-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
    .admin-chat-fixed { flex: 0 0 auto; }
    .admin-chat-bubble { max-width: 72%; overflow-wrap: anywhere; word-break: break-word; }
    @media (max-width: 991.98px) {
        .admin-chat-page { height: auto; max-height: none; min-height: 0; overflow: visible; }
        .admin-chat-panel { height: min(620px, calc(100vh - 140px)); }
    }
</style>

{{-- ===== PAGE ===== --}}
<div class="p-6 admin-chat-page">

    {{-- Heading --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3 admin-chat-heading">
        <div>
            <h1 class="h4 fw-bold mb-1 text-dark">Trò chuyện với khách hàng</h1>
            <p class="text-secondary mb-0 small">Xem và phản hồi các cuộc trò chuyện từ khách hàng.</p>
        </div>
        @if($viewer->isSuperAdmin())
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-success rounded-pill px-3 py-2">Quản trị cấp cao</span>
                <form action="{{ route('admin.chat.index') }}" method="GET" class="d-inline-flex m-0">
                    <select name="branch_id" class="form-select form-select-sm shadow-none" onchange="this.form.submit()" style="min-width:160px;">
                        <option value="">Tất cả chi nhánh</option>
                        @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
    </div>

    <div class="row g-4 admin-chat-grid">

        {{-- ===== LEFT: Conversation list ===== --}}
        <div class="col-12 col-lg-4 col-xl-3">
            <div class="bg-white border rounded-3 shadow-sm overflow-hidden h-100 d-flex flex-column admin-chat-panel">

                {{-- Panel header with total unread badge --}}
                <div class="p-3 border-bottom bg-light admin-chat-fixed d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-bold mb-0 text-dark">Danh sách trò chuyện</h2>
                    @php
                        $totalUnread = $conversations->sum(fn($c) =>
                            $c->messages->where('is_read', false)
                                        ->where('sender_id', $c->user_id)
                                        ->count()
                        );
                    @endphp
                    @if($totalUnread > 0)
                        <span class="badge rounded-pill bg-danger" style="font-size:.75rem;">{{ $totalUnread > 99 ? '99+' : $totalUnread }}</span>
                    @endif
                </div>

                {{-- List --}}
                <div class="admin-chat-scroll">
                    @forelse($conversations as $conv)
                        @php
                            $convUnread = $conv->messages
                                ->where('is_read', false)
                                ->where('sender_id', $conv->user_id)
                                ->count();
                            $isActive = isset($conversation) && $conversation->id === $conv->id;
                        @endphp
                        <a href="{{ route('admin.chat.show', ['conversation' => $conv, 'branch_id' => request('branch_id')]) }}"
                           class="d-block p-3 border-bottom text-decoration-none {{ $isActive ? 'bg-primary-subtle' : 'bg-white' }}">
                            <div class="d-flex align-items-start gap-2">
                                <div class="position-relative flex-shrink-0">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;">
                                        {{ mb_strtoupper(mb_substr($conv->user->name, 0, 1)) }}
                                    </div>
                                    @if($convUnread > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
                                            {{ $convUnread > 9 ? '9+' : $convUnread }}
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <strong class="text-dark small text-truncate {{ $convUnread > 0 ? 'fw-bolder' : '' }}">{{ $conv->user->name }}</strong>
                                        @if($convUnread > 0)
                                            <span class="badge rounded-pill bg-danger flex-shrink-0" style="font-size:.7rem;">{{ $convUnread > 99 ? '99+' : $convUnread }}</span>
                                        @elseif($conv->latestMessage)
                                            <span class="text-secondary small flex-shrink-0">{{ $conv->latestMessage->created_at->format('H:i') }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                                        @if($conv->cskh)
                                            <span class="text-secondary small text-truncate">Phụ trách: {{ $conv->cskh->name }}</span>
                                        @else
                                            <span class="text-warning small text-truncate">Chưa có người phụ trách</span>
                                        @endif
                                        <span class="rounded-circle bg-success flex-shrink-0" style="width:8px;height:8px;"></span>
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

        {{-- ===== RIGHT: Chat panel ===== --}}
        <div class="col-12 col-lg-8 col-xl-9">
            <div class="bg-white border rounded-3 shadow-sm overflow-hidden h-100 d-flex flex-column admin-chat-panel"
                @if(isset($conversation))
                    x-data="adminChat({
                        conversationId: {{ $conversation->id }},
                        canReply: {{ $canReply ? 'true' : 'false' }},
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

                    {{-- Chat header --}}
                    <div class="p-3 border-bottom bg-light admin-chat-fixed">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:38px;height:38px;">
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
                    </div>

                    {{-- Message list --}}
                    <div x-ref="messageList" class="admin-chat-scroll p-3">
                        <div class="d-flex flex-column gap-3">
                            @foreach($conversation->messages as $message)
                                @php
                                    $displaySender     = $message->display_sender;
                                    $isCustomerMessage = $displaySender->id === $conversation->user_id;
                                @endphp
                                <div class="d-flex {{ $isCustomerMessage ? 'justify-content-start' : 'justify-content-end' }}" data-message-id="{{ $message->id }}">
                                    <div class="px-3 py-2 rounded-3 admin-chat-bubble {{ $isCustomerMessage ? 'bg-light text-dark' : 'bg-primary text-white' }}">
                                        @if($message->content)
                                            <p class="small mb-1">{{ $message->content }}</p>
                                        @endif
                                        <p class="mb-0 {{ $isCustomerMessage ? 'text-secondary' : 'text-white-50' }}" style="font-size:.72rem;">
                                            {{ $message->created_at->format('H:i') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- *** INPUT AREA *** --}}
                    <div class="p-3 border-top bg-light admin-chat-fixed">
                        @if($conversation->status === 'closed')
                            <div class="alert alert-warning py-2 small text-center mb-2">
                                Cuộc trò chuyện đã đóng. Nhắn tin để mở lại tự động.
                            </div>
                        @endif

                        @if($canReply)
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
                                    class="btn btn-sm btn-primary px-3 flex-shrink-0"
                                >
                                    <span x-show="!loading">Gửi</span>
                                    <span x-show="loading">Đang gửi...</span>
                                </button>
                            </div>
                        @else
                            <p class="text-secondary text-center small mb-0 py-1">Bạn không có quyền trả lời cuộc trò chuyện này.</p>
                        @endif
                    </div>

                @else
                    {{-- Empty state --}}
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center text-secondary" style="min-height:520px;">
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
        visibilityHandler: null,

        init() {
            this.scrollToBottom();
            this.visibilityHandler = () => document.hidden ? this.stopPolling() : this.startPolling();
            document.addEventListener('visibilitychange', this.visibilityHandler);
            this.startPolling();
        },

        destroy() {
            this.stopPolling();
            document.removeEventListener('visibilitychange', this.visibilityHandler);
        },

        startPolling() {
            if (document.hidden || this.pollInterval) return;
            this.fetchMessages();
            this.pollInterval = window.setInterval(() => this.fetchMessages(), 1000);
        },

        stopPolling() {
            if (!this.pollInterval) return;
            window.clearInterval(this.pollInterval);
            this.pollInterval = null;
        },

        async fetchMessages() {
            try {
                const res  = await fetch(this.messagesUrl, { headers: { 'Accept': 'application/json' } });
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

            container.innerHTML = `<div class="d-flex flex-column gap-3">${messages.map(msg => {
                const isCustomer = msg.sender_id === this.userId;
                const align   = isCustomer ? 'justify-content-start' : 'justify-content-end';
                const bubble  = isCustomer ? 'bg-light text-dark'     : 'bg-primary text-white';
                const tClass  = isCustomer ? 'text-secondary'          : 'text-white-50';
                const time    = new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour:'2-digit', minute:'2-digit', hour12:false });
                return `<div class="d-flex ${align}" data-message-id="${msg.id}">
                    <div class="px-3 py-2 rounded-3 admin-chat-bubble ${bubble}">
                        <p class="small mb-1">${this.escapeHtml(msg.content || '')}</p>
                        <p class="mb-0 ${tClass}" style="font-size:.72rem;">${time}</p>
                    </div>
                </div>`;
            }).join('')}</div>`;

            this.scrollToBottom();
        },

        escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messageList;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        async sendMessage() {
            if (!this.newMessage.trim() || this.loading) return;

            this.loading = true;
            const fd = new FormData();
            fd.append('content',  this.newMessage);
            fd.append('_token',   this.csrfToken);

            try {
                const res  = await fetch(this.replyUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let listPollTimer = null;
        const refreshConversationList = async () => {
            try {
                const url = new URL(window.location.href);
                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (res.ok) {
                    const html = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newList = doc.querySelector('.admin-chat-scroll');
                    const currentList = document.querySelector('.admin-chat-scroll');
                    if (newList && currentList) {
                        if (newList.innerHTML !== currentList.innerHTML) {
                            currentList.innerHTML = newList.innerHTML;
                        }
                    }
                    
                    const newHeaderBadge = doc.querySelector('.admin-chat-fixed .badge');
                    const currentHeaderBadge = document.querySelector('.admin-chat-fixed .badge');
                    if (newHeaderBadge && currentHeaderBadge) {
                        if (currentHeaderBadge.textContent !== newHeaderBadge.textContent) {
                            currentHeaderBadge.textContent = newHeaderBadge.textContent;
                        }
                        currentHeaderBadge.style.display = '';
                    } else if (currentHeaderBadge) {
                        if (!newHeaderBadge) {
                            currentHeaderBadge.style.display = 'none';
                        }
                    } else if (newHeaderBadge) {
                        const newHeader = doc.querySelector('.admin-chat-fixed');
                        const currentHeader = document.querySelector('.admin-chat-fixed');
                        if (newHeader && currentHeader) {
                            currentHeader.innerHTML = newHeader.innerHTML;
                        }
                    }
                }
            } catch (err) {
                console.warn("Lỗi cập nhật danh sách cuộc trò chuyện:", err);
            }
        };
        const startListPolling = () => {
            if (document.hidden || listPollTimer !== null) return;
            refreshConversationList();
            listPollTimer = window.setInterval(refreshConversationList, 3000);
        };
        const stopListPolling = () => {
            if (listPollTimer === null) return;
            window.clearInterval(listPollTimer);
            listPollTimer = null;
        };
        document.addEventListener('visibilitychange', () => document.hidden ? stopListPolling() : startListPolling());
        window.addEventListener('pagehide', stopListPolling, { once: true });
        startListPolling();
    });
</script>
@endsection
