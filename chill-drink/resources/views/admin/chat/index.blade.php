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
        padding: 1.5rem;
        padding-bottom: 0;
    }
    .admin-chat-heading { 
        margin-bottom: 1.5rem;
    }
    
    /* Conversation list */
    .admin-chat-list-panel { 
        height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .admin-chat-scroll { 
        flex: 1 1 auto; 
        min-height: 0; 
        overflow-y: auto; 
        overflow-x: hidden;
        overscroll-behavior: contain;
    }
    
    /* Chat Boxes Container - Fixed at bottom right */
    .chat-boxes-container {
        position: fixed;
        bottom: 0;
        right: 20px;
        display: flex;
        gap: 10px;
        align-items: flex-end;
        z-index: 1050;
        pointer-events: none;
    }
    
    /* Individual Chat Box */
    .chat-box {
        width: 330px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 10px 10px 0 0;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        pointer-events: auto;
        transition: height 0.2s ease;
    }
    
    .chat-box.minimized {
        height: 48px !important;
    }
    
    .chat-box:not(.minimized) {
        height: 450px;
    }
    
    /* Chat Box Header */
    .chat-box-header {
        background: #0084ff;
        color: white;
        padding: 0.6rem 0.8rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 10px 10px 0 0;
        cursor: pointer;
        user-select: none;
    }
    
    .chat-box-header-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        flex: 1;
    }
    
    .chat-box-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    
    .chat-box-title {
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .chat-box-actions {
        display: flex;
        gap: 0.3rem;
        flex-shrink: 0;
    }
    
    .chat-box-btn {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
        padding: 0;
    }
    
    .chat-box-btn:hover {
        background: rgba(255,255,255,0.35);
    }
    
    /* Chat Box Body */
    .chat-box-body {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.75rem;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .chat-box.minimized .chat-box-body {
        display: none;
    }
    
    /* Chat Box Footer */
    .chat-box-footer {
        padding: 0.6rem 0.8rem;
        background: white;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 0.5rem;
    }
    
    .chat-box.minimized .chat-box-footer {
        display: none;
    }
    
    .chat-box-input {
        flex: 1;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
        outline: none;
    }
    
    .chat-box-input:focus {
        border-color: #0084ff;
    }
    
    .chat-box-send-btn {
        background: #0084ff;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    
    .chat-box-send-btn:hover:not(:disabled) {
        background: #0073e6;
    }
    
    .chat-box-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Chat Messages */
    .chat-msg {
        display: flex;
        margin-bottom: 0.5rem;
    }
    
    .chat-msg.from-customer {
        justify-content: flex-start;
    }
    
    .chat-msg.from-admin {
        justify-content: flex-end;
    }
    
    .chat-msg-bubble {
        max-width: 75%;
        padding: 0.5rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    .chat-msg.from-customer .chat-msg-bubble {
        background: white;
        color: #212529;
        border: 1px solid #e9ecef;
    }
    
    .chat-msg.from-admin .chat-msg-bubble {
        background: #0084ff;
        color: white;
    }
    
    .chat-msg-time {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 0.2rem;
    }
    
    @media (max-width: 991.98px) {
        .chat-box {
            width: 280px;
        }
        .chat-boxes-container {
            right: 10px;
            gap: 8px;
        }
    }
</style>

{{-- ===== PAGE ===== --}}
<div class="admin-chat-page" x-data="chatManager()">

    {{-- Heading --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 admin-chat-heading">
        <div>
            <h1 class="h4 fw-bold mb-1 text-dark">Trò chuyện với khách hàng</h1>
            <p class="text-secondary mb-0 small">Nhấn vào cuộc trò chuyện để mở cửa sổ chat</p>
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

    <div class="row g-4">
        {{-- ===== Conversation List ===== --}}
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="bg-white border rounded-3 shadow-sm overflow-hidden admin-chat-list-panel">
                <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
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

                <div class="admin-chat-scroll">
                    @forelse($conversations as $conv)
                        @php
                            $convUnread = $conv->messages
                                ->where('is_read', false)
                                ->where('sender_id', $conv->user_id)
                                ->count();
                        @endphp
                        <button
                            type="button"
                            @click="openChat({{ $conv->id }}, '{{ addslashes($conv->user->name) }}', '{{ addslashes($conv->user->email) }}', {{ $conv->user_id }}, {{ ($viewer->isAdmin() || $conv->cskh_id === null || $conv->cskh_id === $viewer->id) ? 'true' : 'false' }})"
                            class="d-block w-100 p-3 border-bottom text-start bg-white"
                            style="border: none; cursor: pointer; transition: background 0.2s;"
                            onmouseover="this.style.background='#f8f9fa'"
                            onmouseout="this.style.background='white'"
                        >
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
                        </button>
                    @empty
                        <p class="p-4 text-secondary text-center small mb-0">Chưa có cuộc trò chuyện nào.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== Instructions ===== --}}
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="bg-white border rounded-3 shadow-sm p-5" style="height: calc(100vh - 200px);">
                <div class="h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="bi bi-chat-dots display-3 text-primary mb-3"></i>
                        <h3 class="h5 fw-bold text-dark mb-2">Chọn cuộc trò chuyện để bắt đầu</h3>
                        <p class="text-secondary mb-3">Nhấn vào khách hàng bên trái để mở cửa sổ chat</p>
                        <div class="alert alert-info d-inline-block">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Bạn có thể mở tối đa 3 cửa sổ chat cùng lúc</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Chat Boxes (Fixed at bottom) ===== --}}
    <div class="chat-boxes-container">
        <template x-for="(chatBox, index) in openChats" :key="chatBox.id">
            <div class="chat-box" :class="{ 'minimized': chatBox.minimized }">
                {{-- Header --}}
                <div class="chat-box-header" @click="toggleMinimize(chatBox.id)">
                    <div class="chat-box-header-left">
                        <div class="chat-box-avatar" x-text="chatBox.userName.charAt(0).toUpperCase()"></div>
                        <div class="chat-box-title" x-text="chatBox.userName"></div>
                    </div>
                    <div class="chat-box-actions" @click.stop>
                        <button class="chat-box-btn" @click="toggleMinimize(chatBox.id)" :title="chatBox.minimized ? 'Mở rộng' : 'Thu nhỏ'">
                            <i class="bi" :class="chatBox.minimized ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        </button>
                        <button class="chat-box-btn" @click="closeChat(chatBox.id)" title="Đóng">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Body (Messages) --}}
                <div class="chat-box-body" :id="'chatBody_' + chatBox.id">
                    <template x-for="msg in chatBox.messages" :key="msg.id">
                        <div class="chat-msg" :class="msg.isCustomer ? 'from-customer' : 'from-admin'">
                            <div class="chat-msg-bubble">
                                <div x-text="msg.content"></div>
                                <div class="chat-msg-time" x-text="msg.time"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer (Input) --}}
                <div class="chat-box-footer" x-show="chatBox.canReply">
                    <input 
                        type="text" 
                        class="chat-box-input" 
                        placeholder="Nhập tin nhắn..."
                        x-model="chatBox.newMessage"
                        @keydown.enter="sendMessage(chatBox.id)"
                        :disabled="chatBox.sending"
                    >
                    <button 
                        class="chat-box-send-btn" 
                        @click="sendMessage(chatBox.id)"
                        :disabled="chatBox.sending || !chatBox.newMessage.trim()"
                    >
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

</div>

<script>
function chatManager() {
    return {
        openChats: [],
        pollIntervals: {},
        viewerId: {{ $viewer->id }},
        csrfToken: '{{ csrf_token() }}',
        maxChats: 3,

        openChat(conversationId, userName, userEmail, userId, canReply) {
            // Check if already open
            if (this.openChats.find(c => c.id === conversationId)) {
                const chat = this.openChats.find(c => c.id === conversationId);
                chat.minimized = false;
                return;
            }

            // Check max limit
            if (this.openChats.length >= this.maxChats) {
                alert(`Bạn chỉ có thể mở tối đa ${this.maxChats} cửa sổ chat cùng lúc.`);
                return;
            }

            // Create new chat box
            const newChat = {
                id: conversationId,
                userName: userName,
                userEmail: userEmail,
                userId: userId,
                canReply: canReply,
                messages: [],
                newMessage: '',
                sending: false,
                minimized: false
            };

            this.openChats.push(newChat);
            
            // Fetch messages and start polling
            this.fetchMessages(conversationId);
            this.startPolling(conversationId);
        },

        closeChat(conversationId) {
            this.stopPolling(conversationId);
            this.openChats = this.openChats.filter(c => c.id !== conversationId);
        },

        toggleMinimize(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (chat) {
                chat.minimized = !chat.minimized;
                if (!chat.minimized) {
                    this.$nextTick(() => this.scrollToBottom(conversationId));
                }
            }
        },

        async fetchMessages(conversationId) {
            try {
                const res = await fetch(`/admin/chat/${conversationId}/messages`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.success) {
                    const chat = this.openChats.find(c => c.id === conversationId);
                    if (chat) {
                        if (typeof data.can_reply !== 'undefined') {
                            chat.canReply = data.can_reply;
                        }
                        chat.messages = data.messages.map(msg => ({
                            id: msg.id,
                            content: msg.content,
                            isCustomer: msg.sender_id === chat.userId,
                            time: new Date(msg.created_at).toLocaleTimeString('vi-VN', { 
                                hour: '2-digit', 
                                minute: '2-digit' 
                            })
                        }));
                        this.$nextTick(() => this.scrollToBottom(conversationId));
                    }
                }
            } catch (e) {
                console.error('Error fetching messages:', e);
            }
        },

        startPolling(conversationId) {
            if (this.pollIntervals[conversationId]) return;
            
            this.pollIntervals[conversationId] = setInterval(() => {
                if (!document.hidden) {
                    this.fetchMessages(conversationId);
                }
            }, 2000);
        },

        stopPolling(conversationId) {
            if (this.pollIntervals[conversationId]) {
                clearInterval(this.pollIntervals[conversationId]);
                delete this.pollIntervals[conversationId];
            }
        },

        async sendMessage(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat || !chat.newMessage.trim() || chat.sending) return;

            chat.sending = true;
            const fd = new FormData();
            fd.append('content', chat.newMessage);
            fd.append('_token', this.csrfToken);

            try {
                const res = await fetch(`/admin/chat/${conversationId}/reply`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd
                });
                const data = await res.json();
                
                if (data.success) {
                    chat.newMessage = '';
                    await this.fetchMessages(conversationId);
                } else {
                    alert(data.message || 'Không thể gửi tin nhắn.');
                }
            } catch (e) {
                alert('Lỗi kết nối.');
            } finally {
                chat.sending = false;
            }
        },

        scrollToBottom(conversationId) {
            const el = document.getElementById('chatBody_' + conversationId);
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        }
    };
}
</script>

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
                    
                    const newHeaderBadge = doc.querySelector('.admin-chat-list-panel .badge');
                    const currentHeaderBadge = document.querySelector('.admin-chat-list-panel .badge');
                    if (newHeaderBadge && currentHeaderBadge) {
                        if (currentHeaderBadge.textContent !== newHeaderBadge.textContent) {
                            currentHeaderBadge.textContent = newHeaderBadge.textContent;
                        }
                        currentHeaderBadge.style.display = '';
                    } else if (currentHeaderBadge && !newHeaderBadge) {
                        currentHeaderBadge.style.display = 'none';
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
