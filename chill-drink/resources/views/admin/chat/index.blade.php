@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

@section('page-title', 'Chat khách hàng')
@section('hide-topbar-search')

@section('content')
@php $viewer = auth()->user(); @endphp

<style>
.admin-chat-page { padding: 1.5rem 1.5rem 0; }
.admin-chat-heading { margin-bottom: 1.5rem; }

.admin-chat-list-panel {
    height: calc(100vh - 200px);
    display: flex; flex-direction: column; overflow: hidden;
}
.admin-chat-scroll {
    flex: 1 1 auto; min-height: 0;
    overflow-y: auto; overflow-x: hidden;
    overscroll-behavior: contain;
}

/* Conv item */
.conv-item {
    display: block; width: 100%; padding: .75rem 1rem;
    border: none; border-bottom: 1px solid #f0f0f0;
    text-align: left; background: #fff; cursor: pointer;
    transition: background .2s ease, transform .15s ease;
}
.conv-item:hover { background: #f0f7ff; transform: translateX(3px); }
.conv-item.active { background: #e7f3ff; }

/* Chat boxes – open windows: hàng ngang từ phải sang trái */
.chat-boxes-container {
    position: fixed; bottom: 20px; right: 72px;
    display: flex; flex-direction: row-reverse; gap: 10px; align-items: flex-end;
    z-index: 1050; pointer-events: none;
}

/* Minimized icons – cột dọc sát góc phải */
.chat-minimized-container {
    position: fixed; bottom: 20px; right: 20px;
    display: flex; flex-direction: column-reverse; gap: 8px; align-items: center;
    z-index: 1051; pointer-events: none;
}

/* Animation khi chat box xuất hiện */
@keyframes chatSlideUp {
    from { opacity: 0; transform: translateY(24px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}
@keyframes chatSlideDown {
    from { opacity: 1; transform: translateY(0)    scale(1); }
    to   { opacity: 0; transform: translateY(24px) scale(0.96); }
}

/* Animation cho icon minimize xuất hiện */
@keyframes popIn {
    from { opacity: 0; transform: scale(0.5); }
    to   { opacity: 1; transform: scale(1); }
}

/* Animation tin nhắn mới */
@keyframes msgFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.chat-box {
    width: 330px; background: #fff;
    border: 1px solid #dee2e6; border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.08);
    display: flex; flex-direction: column;
    pointer-events: auto;
    height: 450px;
    animation: chatSlideUp .28s cubic-bezier(.34,1.56,.64,1) both;
    transform-origin: bottom right;
}

/* Minimized icon tròn – dùng trong .chat-minimized-container */
.chat-mini-icon {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a8fff 0%, #0065d0 100%);
    box-shadow: 0 4px 16px rgba(0,132,255,.4);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; pointer-events: auto;
    position: relative; flex-shrink: 0;
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease;
    animation: popIn .25s cubic-bezier(.34,1.56,.64,1) both;
}
.chat-mini-icon:hover {
    transform: scale(1.12);
    box-shadow: 0 6px 24px rgba(0,132,255,.55);
}
.chat-mini-icon:active { transform: scale(0.96); }
.chat-mini-icon i { color: #fff; font-size: 1.4rem; }

/* Badge unread trên icon minimized */
.chat-box-minimized-badge {
    display: none;
    position: absolute; top: -4px; right: -4px;
    min-width: 20px; height: 20px; padding: 0 5px;
    border-radius: 999px; background: #dc3545; color: #fff;
    border: 2px solid #fff; font-size: 11px; font-weight: 800;
    align-items: center; justify-content: center;
    animation: popIn .2s ease both;
}
.chat-mini-icon .chat-box-minimized-badge { display: flex; }

.chat-box-header {
    background: linear-gradient(135deg, #1a8fff 0%, #0065d0 100%);
    color: #fff;
    padding: .65rem .85rem; border-radius: 12px 12px 0 0;
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer; user-select: none; flex-shrink: 0;
    position: relative;
    transition: filter .2s ease;
}
.chat-box-header:hover { filter: brightness(1.08); }
.chat-box-header-left { display: flex; align-items: center; gap: .5rem; min-width: 0; flex: 1; }
.chat-box-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .85rem; flex-shrink: 0;
    transition: transform .2s ease;
}
.chat-box-header:hover .chat-box-avatar { transform: scale(1.1); }
.chat-box-title { font-size: .9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat-box-actions { display: flex; gap: .3rem; flex-shrink: 0; }
.chat-box-btn {
    background: rgba(255,255,255,.2); border: none; color: #fff;
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; padding: 0;
    transition: background .2s ease, transform .15s ease;
}
.chat-box-btn:hover { background: rgba(255,255,255,.38); transform: scale(1.1); }
.chat-box-btn:active { transform: scale(0.92); }

.chat-box-body {
    flex: 1; overflow-y: auto; overflow-x: hidden;
    padding: .75rem; background: #f5f7fb;
    display: flex; flex-direction: column; gap: .5rem;
    scroll-behavior: smooth;
}
.chat-box-body::-webkit-scrollbar { width: 4px; }
.chat-box-body::-webkit-scrollbar-track { background: transparent; }
.chat-box-body::-webkit-scrollbar-thumb { background: #c5cfe0; border-radius: 4px; }
.chat-box-body::-webkit-scrollbar-thumb:hover { background: #0084ff; }

.chat-box-footer {
    padding: .6rem .8rem; background: #fff;
    border-top: 1px solid #e9ecef;
    display: flex; gap: .5rem; flex-shrink: 0;
    border-radius: 0 0 12px 12px;
}
.chat-box-input {
    flex: 1; border: 1.5px solid #dee2e6; border-radius: 20px;
    padding: .4rem .85rem; font-size: .875rem; outline: none;
    transition: border-color .2s ease, box-shadow .2s ease;
    background: #f8f9fa;
}
.chat-box-input:focus {
    border-color: #0084ff;
    box-shadow: 0 0 0 3px rgba(0,132,255,.12);
    background: #fff;
}
.chat-box-send-btn {
    background: #0084ff; color: #fff; border: none;
    border-radius: 50%; width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0;
    transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
    box-shadow: 0 2px 8px rgba(0,132,255,.3);
}
.chat-box-send-btn:hover:not(:disabled) {
    background: #0065d0;
    transform: scale(1.08);
    box-shadow: 0 4px 14px rgba(0,132,255,.45);
}
.chat-box-send-btn:active:not(:disabled) { transform: scale(0.94); }
.chat-box-send-btn:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }

.chat-msg { display: flex; margin-bottom: .4rem; animation: msgFadeIn .22s ease both; }
.chat-msg.from-customer { justify-content: flex-start; }
.chat-msg.from-admin    { justify-content: flex-end; }
.chat-msg-bubble {
    max-width: 75%; padding: .45rem .75rem;
    border-radius: 14px; font-size: .85rem; line-height: 1.45; word-break: break-word;
    transition: box-shadow .2s ease;
}
.chat-msg-bubble:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.from-customer .chat-msg-bubble {
    background: #fff; color: #212529;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}
.from-admin .chat-msg-bubble {
    background: linear-gradient(135deg, #1a8fff 0%, #0065d0 100%);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.chat-msg-time { font-size: .67rem; opacity: .6; margin-top: .18rem; }

@media (max-width: 991.98px) {
    .chat-box { width: 280px; }
    .chat-boxes-container { right: 68px; gap: 8px; }
}
</style>

<div class="admin-chat-page" x-data="chatManager()" x-init="init()">

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
        {{-- Conversation list --}}
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="bg-white border rounded-3 shadow-sm overflow-hidden admin-chat-list-panel">
                <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-bold mb-0 text-dark">Danh sách trò chuyện</h2>
                </div>

                <div class="admin-chat-scroll">
                    {{-- Loading --}}
                    <template x-if="listLoading && conversations.length === 0">
                        <div class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <p class="small text-secondary mt-2 mb-0">Đang tải...</p>
                        </div>
                    </template>

                    {{-- Empty --}}
                    <template x-if="!listLoading && conversations.length === 0">
                        <p class="p-4 text-secondary text-center small mb-0">Chưa có cuộc trò chuyện nào.</p>
                    </template>

                    {{-- List --}}
                    <template x-for="conv in conversations" :key="conv.id">
                        <button
                            type="button"
                            class="conv-item"
                            :class="{ active: openChats.some(c => c.id === conv.id) }"
                            @click="openChat(conv.id, conv.user_name, conv.user_email, conv.user_id, conv.can_reply)"
                        >
                            <div class="d-flex align-items-start gap-2">
                                <div class="position-relative flex-shrink-0">
                                    <!-- Guest avatar: icon người dùng vô danh -->
                                    <template x-if="conv.is_guest">
                                        <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:1.1rem;"><i class="bi bi-incognito" style="line-height:1;"></i></div>
                                    </template>
                                    <!-- User avatar: chữ cái đầu -->
                                    <template x-if="!conv.is_guest">
                                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;" x-text="conv.user_name.charAt(0).toUpperCase()"></div>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-1 min-w-0">
                                            <strong class="text-dark small text-truncate" x-text="conv.user_name"></strong>
                                            <!-- Badge Khách vãng lai -->
                                            <span x-show="conv.is_guest" class="badge rounded-pill flex-shrink-0" style="background:#f59e0b;color:#fff;font-size:.62rem;padding:2px 6px;">Khách VL</span>
                                        </div>
                                        <span x-show="conv.unread > 0" x-text="conv.unread > 99 ? '99+' : conv.unread" class="badge rounded-pill bg-danger flex-shrink-0" style="font-size:.7rem;"></span>
                                        <span x-show="conv.unread === 0 && conv.last_at" x-text="conv.last_at" class="text-secondary small flex-shrink-0"></span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                                        <span x-show="conv.cskh_name" x-text="'Phụ trách: ' + conv.cskh_name" class="text-secondary small text-truncate"></span>
                                        <span x-show="!conv.cskh_name" class="text-warning small text-truncate">Chưa có người phụ trách</span>
                                        <span class="rounded-circle bg-success flex-shrink-0" style="width:8px;height:8px;"></span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="bg-white border rounded-3 shadow-sm p-5" style="height:calc(100vh - 200px);">
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

    {{-- Chat Boxes (open/expanded) --}}
    <div class="chat-boxes-container">
        <template x-for="chatBox in openChats.filter(c => !c.minimized)" :key="chatBox.id">
            <div class="chat-box">

                {{-- Header --}}
                <div class="chat-box-header" @click="toggleMinimize(chatBox.id)">
                    <div class="chat-box-header-left">
                        <div class="chat-box-avatar" x-text="chatBox.userName.charAt(0).toUpperCase()"></div>
                        <div class="chat-box-title" x-text="chatBox.userName"></div>
                    </div>
                    <div class="chat-box-actions" @click.stop>
                        <button class="chat-box-btn" @click="toggleMinimize(chatBox.id)" title="Thu nhỏ">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button class="chat-box-btn" @click="closeChat(chatBox.id)" title="Đóng">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="chat-box-body" :id="'chatBody_' + chatBox.id">
                    <template x-if="chatBox.loading">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <p class="small text-secondary mt-2 mb-0">Đang tải...</p>
                        </div>
                    </template>
                    <template x-if="!chatBox.loading && chatBox.messages.length === 0">
                        <p class="text-center text-secondary small py-3 mb-0">Chưa có tin nhắn nào.</p>
                    </template>
                    <template x-for="msg in chatBox.messages" :key="msg.id">
                        <div class="chat-msg" :class="msg.isSystem ? 'justify-content-center my-2' : (msg.isCustomer ? 'from-customer' : 'from-admin')">
                            <template x-if="msg.isSystem">
                                <div style="max-width: 90%; background: #edf9f5; border: 1px solid #c3ebd9; border-radius: 10px; padding: 0.5rem 0.75rem; color: #0d684d; font-size: 0.78rem; text-align: center;">
                                    <div style="font-weight: 700; color: #00a870; margin-bottom: 0.2rem;">🤖 Hệ thống</div>
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

                {{-- Footer --}}
                <div class="chat-box-footer" x-show="chatBox.canReply">
                    <input
                        type="text"
                        class="chat-box-input"
                        placeholder="Nhập tin nhắn..."
                        x-model="chatBox.newMessage"
                        @keydown.enter.prevent="sendMessage(chatBox.id)"
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

    {{-- Minimized icons – xếp dọc sát góc phải --}}
    <div class="chat-minimized-container">
        <template x-for="chatBox in openChats.filter(c => c.minimized)" :key="chatBox.id">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:auto;">
                <div class="chat-mini-icon" @click="toggleMinimize(chatBox.id)" :title="chatBox.userName">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span
                        class="chat-box-minimized-badge"
                        x-show="chatBox.unreadCount > 0"
                        x-text="chatBox.unreadCount > 9 ? '9+' : chatBox.unreadCount"
                    ></span>
                </div>
                <span x-text="chatBox.userName" style="font-size:0.7rem;font-weight:600;color:#333;max-width:70px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.2;"></span>
            </div>
        </template>
    </div>

</div>

<script>
function chatManager() {
    return {
        /* ─── state ─── */
        conversations: [],
        totalUnread: 0,
        listLoading: true,
        openChats: [],
        echoChannels: {},
        chatPollTimers: {},
        listPollTimer: null,

        viewerId:  {{ $viewer->id }},
        csrfToken: '{{ csrf_token() }}',
        listUrl:   '{{ route('admin.chat.conversations') }}{{ request('branch_id') ? '?branch_id=' . request('branch_id') : '' }}',
        maxChats:  3,

        /* ─── lifecycle ─── */
        init() {
            this.fetchList();
            this.startListPolling();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.stopListPolling();
                else                 this.startListPolling();
            });
        },

        /* ─── conversation list ─── */
        async fetchList() {
            try {
                const res  = await fetch(this.listUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.conversations = data.conversations;
                    this.totalUnread   = data.total_unread;
                }
            } catch (e) {
                console.warn('fetchList error', e);
            } finally {
                this.listLoading = false;
            }
        },

        startListPolling() {
            if (this.listPollTimer) return;
            this.listPollTimer = setInterval(() => {
                if (!document.hidden) this.fetchList();
            }, 1500);
        },

        stopListPolling() {
            clearInterval(this.listPollTimer);
            this.listPollTimer = null;
        },

        /* ─── open / close chat ─── */
        openChat(conversationId, userName, userEmail, userId, canReply) {
            const existing = this.openChats.find(c => c.id === conversationId);
            if (existing) {
                existing.minimized = false;
                this.$nextTick(() => this.scrollToBottom(conversationId));
                return;
            }

            if (this.openChats.length >= this.maxChats) {
                alert(`Bạn chỉ có thể mở tối đa ${this.maxChats} cửa sổ chat cùng lúc.`);
                return;
            }

            this.openChats.push({
                id: conversationId,
                userName, userEmail, userId, canReply,
                messages: [],
                newMessage: '',
                sending: false,
                minimized: false,
                loading: true,
                unreadCount: 0,
            });

            this.fetchMessages(conversationId).then(() => {
                // Refresh list ngay sau khi fetch để badge unread biến mất
                this.fetchList();
                // Cập nhật badge sidebar ngay lập tức
                document.dispatchEvent(new CustomEvent('chat:messages-read'));
            });
            this.subscribeEcho(conversationId, userId);
        },

        closeChat(conversationId) {
            if (window.Echo && this.echoChannels[conversationId]) {
                window.Echo.leave('conversation.' + conversationId);
                delete this.echoChannels[conversationId];
            }
            // Dọn polling timer nếu có
            if (this.chatPollTimers && this.chatPollTimers[conversationId]) {
                clearInterval(this.chatPollTimers[conversationId]);
                delete this.chatPollTimers[conversationId];
            }
            this.openChats = this.openChats.filter(c => c.id !== conversationId);
        },

        toggleMinimize(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat) return;
            chat.minimized = !chat.minimized;
            if (!chat.minimized) {
                chat.unreadCount = 0;
                this.$nextTick(() => this.scrollToBottom(conversationId));
            }
        },

        /* ─── messages ─── */
        async fetchMessages(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat) return;

            try {
                const res = await fetch(`/admin/chat/${conversationId}/messages`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) throw new Error(res.status);

                const data = await res.json();
                if (!data.success) return;

                const c = this.openChats.find(c => c.id === conversationId);
                if (!c) return;

                if (typeof data.can_reply !== 'undefined') c.canReply = data.can_reply;

                const mapped = data.messages.map(msg => ({
                    id:         msg.id,
                    content:    msg.content ?? '',
                    isSystem:   msg.content && msg.content.startsWith('🤖 Hệ thống'),
                    isCustomer: msg.sender_id === c.userId,
                    senderName: msg.sender ? msg.sender.name : (msg.guest_sender_name ?? ''),
                    time:       new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                }));

                const changed = mapped.length !== c.messages.length
                    || mapped.some((m, i) => m.id !== c.messages[i]?.id);

                if (changed) {
                    c.messages = mapped;
                    if (!c.minimized) this.$nextTick(() => this.scrollToBottom(conversationId));
                }
                c.loading = false;

            } catch (e) {
                console.error('fetchMessages error', e);
                const c = this.openChats.find(c => c.id === conversationId);
                if (c) c.loading = false;
            }
        },

        /* ─── WebSocket ─── */
        subscribeEcho(conversationId, userId) {
            if (window.Echo && !this.echoChannels[conversationId]) {
                this.echoChannels[conversationId] = window.Echo
                    .private('conversation.' + conversationId)
                    .listen('.message-sent', (payload) => {
                        const chat = this.openChats.find(c => c.id === conversationId);
                        if (!chat) return;
                        if (chat.messages.some(m => m.id === payload.message_id)) return;

                        const isSys = payload.content && payload.content.startsWith('🤖 Hệ thống');
                        chat.messages.push({
                            id:         payload.message_id,
                            content:    payload.content ?? '',
                            isSystem:   isSys,
                            isCustomer: payload.sender_id === chat.userId,
                            senderName: payload.sender_name ?? '',
                            time:       new Date(payload.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                        });

                        if (!chat.minimized) this.$nextTick(() => this.scrollToBottom(conversationId));
                        if (chat.minimized && payload.sender_id === chat.userId) {
                            chat.unreadCount = (chat.unreadCount || 0) + 1;
                        }
                        this.fetchList();
                    });
            }

            // Fallback polling mỗi 1.2s để đảm bảo đồng bộ tức thì
            if (!this.chatPollTimers) this.chatPollTimers = {};
            if (!this.chatPollTimers[conversationId]) {
                this.chatPollTimers[conversationId] = setInterval(() => {
                    if (!document.hidden) this.pollMessages(conversationId);
                }, 1200);
            }
        },

        /* ─── Poll messages (fallback khi WebSocket không hoạt động) ─── */
        async pollMessages(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat || chat.minimized) return;

            try {
                const res = await fetch(`/admin/chat/${conversationId}/messages`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.success) return;

                const c = this.openChats.find(c => c.id === conversationId);
                if (!c) return;

                const existingIds = new Set(c.messages.map(m => m.id));
                const newMessages = data.messages.filter(msg => !existingIds.has(msg.id));

                if (newMessages.length > 0) {
                    newMessages.forEach(msg => {
                        const isSys = msg.content && msg.content.startsWith('🤖 Hệ thống');
                        c.messages.push({
                            id:         msg.id,
                            content:    msg.content ?? '',
                            isSystem:   isSys,
                            isCustomer: msg.sender_id === c.userId,
                            senderName: msg.sender ? msg.sender.name : (msg.guest_sender_name ?? ''),
                            time:       new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                        });
                        if (c.minimized && msg.sender_id === c.userId) {
                            c.unreadCount = (c.unreadCount || 0) + 1;
                        }
                    });
                    if (!c.minimized) this.$nextTick(() => this.scrollToBottom(conversationId));
                    this.fetchList();
                }
            } catch (e) {
                // bỏ qua lỗi mạng
            }
        },

        /* ─── send ─── */
        async sendMessage(conversationId) {
            const chat = this.openChats.find(c => c.id === conversationId);
            if (!chat || !chat.newMessage.trim() || chat.sending) return;

            chat.sending = true;
            const text = chat.newMessage;

            // Optimistic append
            const tempId = 'tmp_' + Date.now();
            chat.messages.push({
                id: tempId, content: text, isCustomer: false,
                time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
            });
            chat.newMessage = '';
            this.$nextTick(() => this.scrollToBottom(conversationId));

            const fd = new FormData();
            fd.append('content', text);
            fd.append('_token', this.csrfToken);

            try {
                const res  = await fetch(`/admin/chat/${conversationId}/reply`, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: fd,
                });
                const data = await res.json();

                const c = this.openChats.find(c => c.id === conversationId);
                if (!c) return;

                if (data.success) {
                    // Thay temp bằng tin thật
                    const idx = c.messages.findIndex(m => m.id === tempId);
                    if (idx !== -1) {
                        c.messages.splice(idx, 1, {
                            id:         data.message.id,
                            content:    data.message.content ?? '',
                            isCustomer: false,
                            time:       new Date(data.message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                        });
                    }
                } else {
                    c.messages = c.messages.filter(m => m.id !== tempId);
                    alert(data.message || 'Không thể gửi tin nhắn.');
                }
            } catch (e) {
                const c = this.openChats.find(c => c.id === conversationId);
                if (c) c.messages = c.messages.filter(m => m.id !== tempId);
                alert('Lỗi kết nối.');
            } finally {
                chat.sending = false;
            }
        },

        /* ─── helpers ─── */
        scrollToBottom(conversationId) {
            const el = document.getElementById('chatBody_' + conversationId);
            if (el) el.scrollTop = el.scrollHeight;
        },
    };
}
</script>
@endsection
