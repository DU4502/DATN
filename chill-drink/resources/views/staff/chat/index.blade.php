@extends('layouts.staff')

@section('page-title', 'Chat hỗ trợ')
@section('hide-topbar-search', '1')

@section('content')
@php $viewer = auth()->user(); @endphp

{{-- Nhúng toàn bộ styles từ admin chat --}}
<style>
.admin-chat-page { padding: 1.5rem 1.5rem 0; }
.admin-chat-list-panel { height: calc(100vh - 200px); display:flex;flex-direction:column;overflow:hidden; }
.admin-chat-scroll { flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain; }
.conv-item { display:block;width:100%;padding:.75rem 1rem;border:none;border-bottom:1px solid #f0f0f0;text-align:left;background:#fff;cursor:pointer;transition:background .2s ease; }
.conv-item:hover { background:#f0f7ff; }
.conv-item.active { background:#e7f3ff; }
.chat-boxes-container { position:fixed;bottom:20px;right:72px;display:flex;flex-direction:row-reverse;gap:10px;align-items:flex-end;z-index:1050;pointer-events:none; }
.chat-minimized-container { position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column-reverse;gap:8px;align-items:center;z-index:1051;pointer-events:none; }
@keyframes chatSlideUp { from{opacity:0;transform:translateY(24px) scale(0.96);}to{opacity:1;transform:translateY(0) scale(1);} }
@keyframes popIn { from{opacity:0;transform:scale(0.5);}to{opacity:1;transform:scale(1);} }
@keyframes msgFadeIn { from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);} }
.chat-box { width:330px;background:#fff;border:1px solid #dee2e6;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);display:flex;flex-direction:column;pointer-events:auto;height:450px;animation:chatSlideUp .28s cubic-bezier(.34,1.56,.64,1) both;transform-origin:bottom right; }
.chat-mini-icon { width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#00a870 0%,#007a52 100%);box-shadow:0 4px 16px rgba(0,168,112,.4);display:flex;align-items:center;justify-content:center;cursor:pointer;pointer-events:auto;position:relative;flex-shrink:0;transition:transform .2s,box-shadow .2s;animation:popIn .25s cubic-bezier(.34,1.56,.64,1) both; }
.chat-mini-icon:hover { transform:scale(1.12);box-shadow:0 6px 24px rgba(0,168,112,.55); }
.chat-mini-icon i { color:#fff;font-size:1.4rem; }
.chat-box-minimized-badge { display:none;position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;padding:0 5px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:11px;font-weight:800;align-items:center;justify-content:center;animation:popIn .2s ease both; }
.chat-mini-icon .chat-box-minimized-badge { display:flex; }
.chat-box-header { background:linear-gradient(135deg,#00a870 0%,#007a52 100%);color:#fff;padding:.65rem .85rem;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;flex-shrink:0; }
.chat-box-avatar { width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0; }
.chat-box-title { font-size:.9rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.chat-box-header-left { display:flex;align-items:center;gap:.5rem;min-width:0;flex:1; }
.chat-box-actions { display:flex;gap:.3rem;flex-shrink:0; }
.chat-box-btn { background:rgba(255,255,255,.2);border:none;color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;transition:background .2s; }
.chat-box-btn:hover { background:rgba(255,255,255,.38); }
.chat-box-body { flex:1;overflow-y:auto;overflow-x:hidden;padding:.75rem;background:#f5f7fb;display:flex;flex-direction:column;gap:.5rem;scroll-behavior:smooth; }
.chat-box-body::-webkit-scrollbar { width:4px; }
.chat-box-body::-webkit-scrollbar-thumb { background:#c5cfe0;border-radius:4px; }
.chat-box-footer { padding:.6rem .8rem;background:#fff;border-top:1px solid #e9ecef;display:flex;gap:.5rem;flex-shrink:0;border-radius:0 0 12px 12px; }
.chat-box-input { flex:1;border:1.5px solid #dee2e6;border-radius:20px;padding:.4rem .85rem;font-size:.875rem;outline:none;transition:border-color .2s;background:#f8f9fa; }
.chat-box-input:focus { border-color:#00a870;box-shadow:0 0 0 3px rgba(0,168,112,.12);background:#fff; }
.chat-box-send-btn { background:#00a870;color:#fff;border:none;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:background .2s,transform .15s; }
.chat-box-send-btn:hover:not(:disabled) { background:#007a52;transform:scale(1.08); }
.chat-box-send-btn:disabled { opacity:.45;cursor:not-allowed; }
.chat-msg { display:flex;margin-bottom:.4rem;animation:msgFadeIn .22s ease both; }
.chat-msg.from-customer { justify-content:flex-start; }
.chat-msg.from-admin { justify-content:flex-end; }
.chat-msg-bubble { max-width:75%;padding:.45rem .75rem;border-radius:14px;font-size:.85rem;line-height:1.45;word-break:break-word; }
.from-customer .chat-msg-bubble { background:#fff;color:#212529;border:1px solid #e2e8f0;border-bottom-left-radius:4px; }
.from-admin .chat-msg-bubble { background:linear-gradient(135deg,#00a870 0%,#007a52 100%);color:#fff;border-bottom-right-radius:4px; }
.chat-msg-time { font-size:.67rem;opacity:.6;margin-top:.18rem; }
</style>

<div class="admin-chat-page" x-data="staffChatManager()" x-init="init()">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1 text-dark">Chat hỗ trợ khách hàng</h1>
            <p class="text-secondary mb-0 small">
                Chi nhánh: <strong>{{ auth()->user()->branch?->name ?? 'Chưa gán' }}</strong> — Nhấn vào cuộc trò chuyện để mở
            </p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Danh sách conversation --}}
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="bg-white border rounded-3 shadow-sm overflow-hidden admin-chat-list-panel">
                <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-bold mb-0 text-dark">Danh sách trò chuyện</h2>
                    <span x-show="totalUnread > 0" x-text="totalUnread + ' chưa đọc'" class="badge bg-danger rounded-pill small"></span>
                </div>
                <div class="p-2 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo tên, email..."
                               x-model="searchKeyword" @input.debounce.300ms="fetchList()">
                    </div>
                </div>
                <div class="admin-chat-scroll">
                    <template x-if="listLoading && conversations.length === 0">
                        <div class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <p class="small text-secondary mt-2 mb-0">Đang tải...</p>
                        </div>
                    </template>
                    <template x-if="!listLoading && conversations.length === 0">
                        <p class="p-4 text-secondary text-center small mb-0">Chưa có cuộc trò chuyện nào.</p>
                    </template>
                    <template x-for="conv in conversations" :key="conv.id">
                        <button type="button" class="conv-item"
                            :class="{ active: openChats.some(c => c.id === conv.id) }"
                            @click="openChat(conv.id, conv.user_name, conv.user_email, conv.user_id, conv.can_reply)">
                            <div class="d-flex align-items-start gap-2">
                                <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                     :style="conv.is_guest ? 'width:36px;height:36px;background:#6b7280;font-size:1rem;' : 'width:36px;height:36px;background:#00a870;'">
                                    <template x-if="conv.is_guest"><i class="bi bi-person" style="line-height:1;font-size:.9rem;"></i></template>
                                    <template x-if="!conv.is_guest"><span x-text="conv.user_name.charAt(0).toUpperCase()"></span></template>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-1 min-w-0">
                                            <strong class="text-dark small text-truncate" x-text="conv.user_name"></strong>
                                            <span x-show="conv.is_guest" class="badge rounded-pill flex-shrink-0" style="background:#f59e0b;color:#fff;font-size:.62rem;padding:2px 6px;">KVL</span>
                                        </div>
                                        <span x-show="conv.unread > 0" x-text="conv.unread > 99 ? '99+' : conv.unread" class="badge rounded-pill bg-danger flex-shrink-0" style="font-size:.7rem;"></span>
                                        <span x-show="conv.unread === 0 && conv.last_at" x-text="conv.last_at" class="text-secondary small flex-shrink-0"></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span x-show="conv.cskh_name" x-text="'Phụ trách: ' + conv.cskh_name" class="text-secondary small text-truncate"></span>
                                        <span x-show="!conv.cskh_name" class="text-warning small">Chưa có người phụ trách</span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Hướng dẫn --}}
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="bg-white border rounded-3 shadow-sm p-5" style="height:calc(100vh - 200px);">
                <div class="h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="bi bi-chat-dots display-3 mb-3" style="color:#00a870;"></i>
                        <h3 class="h5 fw-bold text-dark mb-2">Chọn cuộc trò chuyện để bắt đầu</h3>
                        <p class="text-secondary mb-3">Nhấn vào khách hàng bên trái để mở cửa sổ chat</p>
                        <div class="alert alert-info d-inline-block">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Mở tối đa 3 cửa sổ cùng lúc</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chat boxes mở --}}
    <div class="chat-boxes-container">
        <template x-for="chatBox in openChats.filter(c => !c.minimized)" :key="chatBox.id">
            <div class="chat-box">
                <div class="chat-box-header" @click="toggleMinimize(chatBox.id)">
                    <div class="chat-box-header-left">
                        <div class="chat-box-avatar" x-text="chatBox.userName.charAt(0).toUpperCase()"></div>
                        <div class="chat-box-title" x-text="chatBox.userName"></div>
                    </div>
                    <div class="chat-box-actions" @click.stop>
                        <button class="chat-box-btn" @click="toggleMinimize(chatBox.id)" title="Thu nhỏ"><i class="bi bi-chevron-down"></i></button>
                        <button class="chat-box-btn" @click="closeChat(chatBox.id)" title="Đóng"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
                <div class="chat-box-body" :id="'chatBody_' + chatBox.id">
                    <template x-if="chatBox.loading">
                        <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
                    </template>
                    <template x-if="!chatBox.loading && chatBox.messages.length === 0">
                        <p class="text-center text-secondary small py-3 mb-0">Chưa có tin nhắn nào.</p>
                    </template>
                    <template x-for="msg in chatBox.messages" :key="msg.id">
                        <div class="chat-msg" :class="msg.isSystem ? 'justify-content-center my-2' : (msg.isCustomer ? 'from-customer' : 'from-admin')">
                            <template x-if="msg.isSystem">
                                <div style="max-width:90%;background:#edf9f5;border:1px solid #c3ebd9;border-radius:10px;padding:.5rem .75rem;color:#0d684d;font-size:.78rem;text-align:center;">
                                    <div style="font-weight:700;color:#00a870;margin-bottom:.2rem;">🤖 Hệ thống</div>
                                    <div x-text="msg.content.replace('🤖 Hệ thống\n', '').replace('🤖 Hệ thống', '')" style="white-space:pre-line;"></div>
                                    <div class="chat-msg-time text-center mt-1" x-text="msg.time"></div>
                                </div>
                            </template>
                            <template x-if="!msg.isSystem">
                                <div class="chat-msg-bubble">
                                    <template x-if="!msg.isCustomer && msg.senderName">
                                        <div style="font-size:0.7rem;font-weight:700;margin-bottom:2px;opacity:0.9;" x-text="msg.senderName"></div>
                                    </template>
                                    <div x-text="msg.content" style="white-space:pre-line;"></div>
                                    <div class="chat-msg-time" x-text="msg.time"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="chat-box-footer" x-show="chatBox.canReply">
                    <input type="text" class="chat-box-input" placeholder="Nhập tin nhắn..."
                           x-model="chatBox.newMessage" @keydown.enter.prevent="sendMessage(chatBox.id)"
                           :disabled="chatBox.sending">
                    <button class="chat-box-send-btn" @click="sendMessage(chatBox.id)"
                            :disabled="chatBox.sending || !chatBox.newMessage.trim()">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Minimized icons --}}
    <div class="chat-minimized-container">
        <template x-for="chatBox in openChats.filter(c => c.minimized)" :key="chatBox.id">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:auto;">
                <div class="chat-mini-icon" @click="toggleMinimize(chatBox.id)" :title="chatBox.userName">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span class="chat-box-minimized-badge" x-show="chatBox.unreadCount > 0"
                          x-text="chatBox.unreadCount > 9 ? '9+' : chatBox.unreadCount"></span>
                </div>
                <span x-text="chatBox.userName" style="font-size:.7rem;font-weight:600;color:#333;max-width:70px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            </div>
        </template>
    </div>
</div>

<script>
function staffChatManager() {
    return {
        conversations: [], totalUnread: 0, listLoading: true,
        openChats: [], echoChannels: {}, chatPollTimers: {}, listPollTimer: null,
        searchKeyword: '',
        viewerId: {{ $viewer->id }},
        csrfToken: '{{ csrf_token() }}',
        listUrl: '{{ route('staff.chat.conversations') }}',
        maxChats: 3,

        init() {
            this.fetchList();
            this.startListPolling();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.stopListPolling(); else this.startListPolling();
            });
        },

        async fetchList() {
            try {
                const url = this.searchKeyword.trim()
                    ? this.listUrl + '?q=' + encodeURIComponent(this.searchKeyword.trim())
                    : this.listUrl;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.success) { this.conversations = data.conversations; this.totalUnread = data.total_unread; }
            } catch(e) {} finally { this.listLoading = false; }
        },

        startListPolling() {
            if (this.listPollTimer) return;
            this.listPollTimer = setInterval(() => { if (!document.hidden) this.fetchList(); }, 1500);
        },
        stopListPolling() { clearInterval(this.listPollTimer); this.listPollTimer = null; },

        openChat(conversationId, userName, userEmail, userId, canReply) {
            const existing = this.openChats.find(c => c.id === conversationId);
            if (existing) { existing.minimized = false; this.$nextTick(() => this.scrollToBottom(conversationId)); return; }
            if (this.openChats.length >= this.maxChats) { alert(`Tối đa ${this.maxChats} cửa sổ.`); return; }
            this.openChats.push({ id: conversationId, userName, userEmail, userId, canReply, messages: [], newMessage: '', sending: false, minimized: false, loading: true, unreadCount: 0 });
            this.fetchMessages(conversationId).then(() => {
                this.fetchList();
                document.dispatchEvent(new CustomEvent('chat:messages-read'));
            });
            this.subscribeEcho(conversationId, userId);
        },

        closeChat(conversationId) {
            if (window.Echo && this.echoChannels[conversationId]) {
                window.Echo.leave('conversation.' + conversationId); delete this.echoChannels[conversationId];
            }
            if (this.chatPollTimers[conversationId]) { clearInterval(this.chatPollTimers[conversationId]); delete this.chatPollTimers[conversationId]; }
            this.openChats = this.openChats.filter(c => c.id !== conversationId);
        },

        toggleMinimize(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat) return;
            chat.minimized = !chat.minimized;
            if (!chat.minimized) { chat.unreadCount = 0; this.$nextTick(() => this.scrollToBottom(conversationId)); }
        },

        async fetchMessages(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat) return;
            try {
                const res = await fetch(`/staff/chat/${conversationId}/messages`, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(res.status);
                const data = await res.json();
                if (!data.success) return;
                const c = this.openChats.find(c => c.id === conversationId);
                if (!c) return;
                if (typeof data.can_reply !== 'undefined') c.canReply = data.can_reply;
                const mapped = data.messages.map(msg => ({
                    id: msg.id, content: msg.content ?? '',
                    isSystem: msg.content && msg.content.startsWith('🤖 Hệ thống'),
                    isCustomer: msg.sender_id === c.userId,
                    senderName: msg.sender ? msg.sender.name : (msg.guest_sender_name ?? ''),
                    time: new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                }));
                const changed = mapped.length !== c.messages.length || mapped.some((m, i) => m.id !== c.messages[i]?.id);
                if (changed) { c.messages = mapped; if (!c.minimized) this.$nextTick(() => this.scrollToBottom(conversationId)); }
                c.loading = false;
            } catch(e) { const c = this.openChats.find(c => c.id === conversationId); if (c) c.loading = false; }
        },

        subscribeEcho(conversationId, userId) {
            if (window.Echo && !this.echoChannels[conversationId]) {
                this.echoChannels[conversationId] = window.Echo.private('conversation.' + conversationId)
                    .listen('.message-sent', (payload) => {
                        const chat = this.openChats.find(c => c.id === conversationId);
                        if (!chat || chat.messages.some(m => m.id === payload.message_id)) return;
                        const isSys = payload.content && payload.content.startsWith('🤖 Hệ thống');
                        chat.messages.push({ id: payload.message_id, content: payload.content ?? '', isSystem: isSys, isCustomer: payload.sender_id === chat.userId, senderName: payload.sender_name ?? '', time: new Date(payload.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) });
                        if (!chat.minimized) this.$nextTick(() => this.scrollToBottom(conversationId));
                        if (chat.minimized && payload.sender_id === chat.userId) chat.unreadCount = (chat.unreadCount || 0) + 1;
                        this.fetchList();
                    });
            }
            if (!this.chatPollTimers[conversationId]) {
                this.chatPollTimers[conversationId] = setInterval(() => { if (!document.hidden) this.pollMessages(conversationId); }, 1500);
            }
        },

        async pollMessages(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat || chat.minimized) return;
            try {
                const res = await fetch(`/staff/chat/${conversationId}/messages`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.success) return;
                const c = this.openChats.find(c => c.id === conversationId);
                if (!c) return;
                const existingIds = new Set(c.messages.map(m => m.id));
                const newMsgs = data.messages.filter(msg => !existingIds.has(msg.id));
                if (newMsgs.length > 0) {
                    newMsgs.forEach(msg => {
                        const isSys = msg.content && msg.content.startsWith('🤖 Hệ thống');
                        c.messages.push({ id: msg.id, content: msg.content ?? '', isSystem: isSys, isCustomer: msg.sender_id === c.userId, senderName: msg.sender ? msg.sender.name : (msg.guest_sender_name ?? ''), time: new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) });
                        if (c.minimized && msg.sender_id === c.userId) c.unreadCount = (c.unreadCount || 0) + 1;
                    });
                    if (!c.minimized) this.$nextTick(() => this.scrollToBottom(conversationId));
                    this.fetchList();
                }
            } catch(e) {}
        },

        async sendMessage(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat || !chat.newMessage.trim() || chat.sending) return;
            chat.sending = true;
            const text = chat.newMessage;
            const tempId = 'tmp_' + Date.now();
            chat.messages.push({ id: tempId, content: text, isCustomer: false, isSystem: false, time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) });
            chat.newMessage = '';
            this.$nextTick(() => this.scrollToBottom(conversationId));
            const fd = new FormData();
            fd.append('content', text);
            fd.append('_token', this.csrfToken);
            try {
                const res = await fetch(`/staff/chat/${conversationId}/reply`, { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!data.success) { chat.messages = chat.messages.filter(m => m.id !== tempId); chat.newMessage = text; }
                else {
                    const idx = chat.messages.findIndex(m => m.id === tempId);
                    if (idx !== -1) { const msg = data.message; chat.messages.splice(idx, 1, { id: msg.id, content: msg.content ?? '', isCustomer: false, isSystem: false, time: new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) }); }
                    this.fetchList();
                }
            } catch(e) { chat.messages = chat.messages.filter(m => m.id !== tempId); chat.newMessage = text; }
            finally { chat.sending = false; }
        },

        scrollToBottom(conversationId) {
            const el = document.getElementById('chatBody_' + conversationId);
            if (el) el.scrollTop = el.scrollHeight;
        },
    };
}
</script>

@endsection
