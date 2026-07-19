<style>
    [x-cloak] { display: none !important; }
</style>

<div
    x-data="{
        isOpen: false,
        menuOpen: false,
        groupChatAvailable: false,
        groupChatOpen: false,
        groupUnread: 0,
        supportUnread: 0,
        conversationId: null,
        currentUserId: {{ (int) (auth()->id() ?? 0) }},
        branchId: null,
        branchName: '',
        nearestBranches: [],
        loadingLocation: false,
        locationState: 'prompt', // 'prompt' | 'granted' | 'denied'
        needLogin: false,
        selectingBranch: false,
        selectedBranchNameTemp: '',
        messages: [],
        newMessage: '',
        loading: false,
        pollInterval: null,
        unreadPollInterval: null,
        echoChannel: null,
        visibilityHandler: null,
        _activating: false,

        get hasUserSentMessage() {
            return this.messages.some(m => Number(m.sender_id) === Number(this.currentUserId));
        },

        async init() {
            this.groupChatAvailable = Boolean(document.querySelector('[data-vue-group-chat]'));
            
            if (localStorage.getItem('support_chat_open') === 'true') {
                this.isOpen = true;
            }

            window.addEventListener('group-chat-unread', (event) => {
                this.groupUnread = Number(event.detail?.count || 0);
            });
            window.addEventListener('group-chat-opened', () => {
                this.isOpen = false;
                this.menuOpen = false;
                this.groupChatOpen = true;
                localStorage.setItem('support_chat_open', 'false');
            });
            window.addEventListener('group-chat-closed', () => {
                this.groupChatOpen = false;
            });
            window.addEventListener('support-chat-close', () => {
                this.isOpen = false;
                this.menuOpen = false;
                localStorage.setItem('support_chat_open', 'false');
            });
            this.visibilityHandler = () => {
                if (document.hidden) {
                    this.stopPolling();
                    this.stopUnreadPolling();
                } else if (this.isOpen) {
                    this.activateSupportChat();
                } else {
                    this.startUnreadPolling();
                }
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);
            this.$watch('isOpen', (isOpen) => {
                localStorage.setItem('support_chat_open', isOpen ? 'true' : 'false');
                if (isOpen) {
                    this.supportUnread = 0;
                    this.stopUnreadPolling();
                    this.activateSupportChat();
                } else {
                    this.stopPolling();
                    // Giữ Echo subscribe để nhận thông báo unread real-time khi chat đóng
                    this.startUnreadPolling();
                }
            });

            // Nếu chat đang mở (từ localStorage) → kích hoạt ngược lại
            if (this.isOpen) {
                this.activateSupportChat();
            } else {
                // Chat đóng: lấy conversationId + đếm unread để hiện badge
                await this.fetchUnreadCount();
                this.subscribeEchoChannel();
                this.startUnreadPolling();
            }
        },

        destroy() {
            this.stopPolling();
            this.stopUnreadPolling();
            this.leaveEchoChannel();
            document.removeEventListener('visibilitychange', this.visibilityHandler);
        },

        async openSupportChat() {
            this.menuOpen = false;
            if (this.isOpen) {
                this.isOpen = false;
                localStorage.setItem('support_chat_open', 'false');
                return;
            }
            window.dispatchEvent(new CustomEvent('group-chat-close'));
            this.isOpen = true; // $watch sẽ tự gọi activateSupportChat()
            localStorage.setItem('support_chat_open', 'true');
        },

        async openGroupChat() {
            this.menuOpen = false;
            this.isOpen = false;
            localStorage.setItem('support_chat_open', 'false');
            window.dispatchEvent(new CustomEvent('group-chat-toggle'));
        },

        async toggleUnifiedChat() {
            if (this.isOpen) {
                this.isOpen = false;
                localStorage.setItem('support_chat_open', 'false');
                return;
            }
            if (this.groupChatOpen) {
                this.groupChatOpen = false;
                window.dispatchEvent(new CustomEvent('group-chat-close'));
                return;
            }
            if (this.groupChatAvailable) {
                this.menuOpen = !this.menuOpen;
                return;
            }
            await this.openSupportChat();
        },

        async activateSupportChat() {
            if (document.hidden || !this.isOpen || this._activating) return;
            this._activating = true;
            try {
                // Luôn gọi server để lấy đúng conversation của user hiện tại
                if (!this.needLogin) {
                    await this.getOrCreateConversation();
                }
                if (!this.conversationId || !this.isOpen || this.needLogin) return;

                if (this.branchId) {
                    await this.fetchMessages(true); // true = mark as read
                    this.subscribeEchoChannel();
                    this.startPolling();
                } else {
                    await this.requestLocationAndFetchBranches();
                }
            } finally {
                this._activating = false;
            }
        },

        async requestLocationAndFetchBranches() {
            this.loadingLocation = true;
            this.locationState = 'prompt';

            if (!navigator.geolocation) {
                this.locationState = 'denied';
                await this.fetchNearestBranches();
                this.loadingLocation = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    this.locationState = 'granted';
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    await this.fetchNearestBranches(lat, lng);
                    this.loadingLocation = false;
                },
                async (err) => {
                    console.warn('Geolocation denied or error', err);
                    this.locationState = 'denied';
                    await this.fetchNearestBranches();
                    this.loadingLocation = false;
                },
                { timeout: 7000, maximumAge: 60000 }
            );
        },

        async fetchNearestBranches(lat = null, lng = null) {
            try {
                let url = '{{ route('chat.nearest-branches') }}';
                const params = new URLSearchParams();
                if (lat !== null) params.append('lat', lat);
                if (lng !== null) params.append('lng', lng);

                if (params.toString()) {
                    url += '?' + params.toString();
                }

                const res = await fetch(url);
                if (res.status === 401 || res.redirected) {
                    this.needLogin = true;
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    this.nearestBranches = data.branches || [];
                }
            } catch (e) {
                console.error('Error fetching nearest branches', e);
            }
        },

        async selectBranch(branch) {
            if (!this.conversationId || this.selectingBranch) return;
            this.selectingBranch = true;
            this.selectedBranchNameTemp = branch.name;

            try {
                const res = await fetch('{{ route('chat.select-branch') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.conversationId,
                        branch_id: branch.id,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.branchId = data.branch_id;
                    this.branchName = data.branch_name;
                    if (data.message) {
                        this.messages = [data.message];
                    }
                    await this.fetchMessages();
                    this.subscribeEchoChannel();
                    this.startPolling();
                }
            } catch (e) {
                console.error('Error selecting branch', e);
            } finally {
                this.selectingBranch = false;
                this.selectedBranchNameTemp = '';
            }
        },

        changeBranch() {
            if (this.hasUserSentMessage) {
                alert('Bạn đã gửi tin nhắn trong cuộc trò chuyện này. Vui lòng tiếp tục hỗ trợ với ' + this.branchName + '.');
                return;
            }
            this.branchId = null;
            this.branchName = '';
            this.messages = [];
            this.stopPolling();
            this.leaveEchoChannel();
            this.requestLocationAndFetchBranches();
        },

        subscribeEchoChannel() {
            if (!window.Echo || !this.conversationId) return;
            if (this.echoChannel) return;

            this.echoChannel = window.Echo.private('conversation.' + this.conversationId)
                .listen('.message-sent', (payload) => {
                    // Bỏ qua tin nhắn do chính user này gửi (tránh duplicate với sendMessage)
                    const alreadyExists = this.messages.some(m => m.id === payload.message_id);
                    if (alreadyExists) return;

                    // Tin từ admin/CSKH (không phải user đang đăng nhập)
                    const isFromAdmin = payload.sender_id !== {{ auth()->id() }};

                    this.messages.push({
                        id: payload.message_id,
                        conversation_id: payload.conversation_id,
                        sender_id: payload.sender_id,
                        content: payload.content,
                        attachment_path: payload.attachment_path,
                        attachment_name: payload.attachment_name,
                        attachment_url: payload.attachment_url,
                        is_read: payload.is_read,
                        created_at: payload.created_at,
                        sender: { id: payload.sender_id, name: payload.sender_name },
                    });

                    // Chat đang đóng → tăng badge unread
                    if (!this.isOpen && isFromAdmin) {
                        this.supportUnread++;
                    }

                    if (this.isOpen) {
                        this.$nextTick(() => { this.scrollToBottom(); });
                    }
                });
        },

        // Fetch unread count từ server (fallback khi Reverb chưa kết nối)
        async fetchUnreadCount() {
            if (!this.conversationId) {
                try {
                    const res = await fetch('{{ route('chat.index') }}');
                    const data = await res.json();
                    if (data.success) {
                        this.conversationId = data.conversation_id;
                        this.branchId       = data.branch_id;
                        this.branchName     = data.branch_name || '';
                        // Có conversationId → subscribe WebSocket ngay
                        this.subscribeEchoChannel();
                    }
                } catch (e) { return; }
            }
            if (!this.conversationId) return;
            try {
                const res  = await fetch('{{ route('chat.messages') }}?conversation_id=' + this.conversationId);
                const data = await res.json();
                if (data.success) {
                    const uid = {{ auth()->id() }};
                    this.supportUnread = data.messages.filter(m => m.sender_id !== uid && !m.is_read).length;
                }
            } catch (e) { /* silent */ }
        },

        startUnreadPolling() {
            if (this.unreadPollInterval || this.isOpen || document.hidden) return;
            this.unreadPollInterval = window.setInterval(() => {
                if (!this.isOpen && !document.hidden) this.fetchUnreadCount();
            }, 1000);
        },

        stopUnreadPolling() {
            if (!this.unreadPollInterval) return;
            window.clearInterval(this.unreadPollInterval);
            this.unreadPollInterval = null;
        },

        leaveEchoChannel() {
            if (!window.Echo || !this.conversationId || !this.echoChannel) return;
            window.Echo.leave('conversation.' + this.conversationId);
            this.echoChannel = null;
        },

        startPolling() {
            this.stopPolling();
            if (!this.isOpen || document.hidden || !this.conversationId || !this.branchId) return;
            this.pollInterval = window.setInterval(() => {
                if (this.isOpen && !document.hidden) this.fetchMessages();
            }, 15000);
        },

        stopPolling() {
            if (!this.pollInterval) return;
            window.clearInterval(this.pollInterval);
            this.pollInterval = null;
        },

        async getOrCreateConversation() {
            try {
                const res = await fetch('{{ route('chat.index') }}');
                if (res.status === 401 || res.redirected) {
                    this.needLogin = true;
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    this.needLogin = false;
                    this.conversationId = data.conversation_id;
                    this.branchId = data.branch_id;
                    this.branchName = data.branch_name || '';
                }
            } catch (e) {
                console.error('Error getting conversation', e);
            }
        },

        async fetchMessages(markRead = false) {
            if (!this.conversationId) return;
            try {
                const url = '{{ route('chat.messages') }}?conversation_id=' + this.conversationId
                    + (markRead ? '&mark_as_read=1' : '');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success && Array.isArray(data.messages)) {
                    this.messages = data.messages;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                    // Reset badge unread khi đã đọc
                    if (markRead) this.supportUnread = 0;
                }
            } catch (e) {
                console.error('Error fetching messages', e);
            }
        },

        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.conversationId || !this.branchId) return;

            this.loading = true;
            const formData = new FormData();
            formData.append('conversation_id', this.conversationId);
            formData.append('content', this.newMessage);

            try {
                const res = await fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    this.messages.push(data.message);
                    this.newMessage = '';
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                } else {
                    console.error('Server error:', data.message);
                    alert(data.message || 'Không thể gửi tin nhắn. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('Error sending message', e);
                alert('Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.');
            } finally {
                this.loading = false;
            }
        },
    }"
    class="fixed bottom-6 right-6 z-50" style="position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1050;"
>
    <!-- Floating Toggle Button (Always visible at bottom right, z-index 1060 above modal window) -->
    <button
        type="button"
        @click.prevent.stop="toggleUnifiedChat()"
        class="flex items-center justify-center rounded-full shadow-2xl transition-all duration-300 hover:scale-110"
        style="position: relative; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; outline: none; z-index: 1060;"
    >
        <svg x-show="!isOpen && !groupChatOpen" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg x-show="isOpen || groupChatOpen" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        {{-- Badge group unread (chỉ hiện khi không có support unread) --}}
        <span x-show="groupUnread > 0 && supportUnread === 0" x-text="groupUnread > 99 ? '99+' : groupUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        {{-- Badge support unread (hỗ trợ khách hàng) --}}
        <span x-show="supportUnread > 0" x-text="supportUnread > 99 ? '99+' : supportUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        {{-- Badge tổng cộng khi có cả 2 --}}
        <template x-if="groupUnread > 0 && supportUnread > 0">
            <span style="position:absolute;bottom:-4px;left:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#5b50d6;color:#fff;border:2px solid #fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;" x-text="groupUnread > 9 ? '9+' : groupUnread"></span>
        </template>
    </button>

    <!-- Unified Menu Popup -->
    <div
        x-show="menuOpen"
        x-cloak
        @click.away="menuOpen = false"
        class="absolute bottom-20 right-0 w-64 rounded-2xl shadow-2xl p-2 border space-y-1 transition-all duration-200"
        style="background: #ffffff; border-color: var(--c-border);"
    >
        <button
            type="button"
            @click.prevent="openSupportChat()"
            class="w-full text-left px-3 py-2.5 rounded-xl transition-all flex items-center justify-between group"
            style="background: transparent;"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='transparent'"
        >
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;">
                    💬
                </div>
                <div>
                    <div class="text-sm font-bold" style="color: var(--c-text);">Hỗ trợ khách hàng</div>
                    <div class="text-xs opacity-70" style="color: var(--c-text);">Trò chuyện trực tiếp với CSKH</div>
                </div>
            </div>
        </button>

        <button
            type="button"
            @click.prevent="openSupportChat()"
            class="w-full text-left px-3 py-2.5 rounded-xl transition-all flex items-center justify-between group"
            style="background: transparent;"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='transparent'"
        >
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;">
                    💬
                </div>
                <div>
                    <div class="text-sm font-bold" style="color: var(--c-text);">Hỗ trợ khách hàng</div>
                    <div class="text-xs opacity-70" style="color: var(--c-text);">Trò chuyện trực tiếp với CSKH</div>
                </div>
            </div>
            <span x-show="supportUnread > 0" x-text="supportUnread > 99 ? '99+' : supportUnread" class="px-2 py-0.5 rounded-full text-xs font-bold text-white" style="background: #dc3545;"></span>
        </button>

        <button
            type="button"
            x-show="groupChatAvailable"
            @click.prevent="openGroupChat()"
            class="w-full text-left px-3 py-2.5 rounded-xl transition-all flex items-center justify-between group"
            style="background: transparent;"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='transparent'"
        >
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: #ec4899; color: white;">
                    👥
                </div>
                <div>
                    <div class="text-sm font-bold flex items-center gap-1.5" style="color: var(--c-text);">
                        <span>Chat Nhóm Chung</span>
                        <span x-show="groupUnread > 0" class="w-2 h-2 rounded-full" style="background: #dc3545;"></span>
                    </div>
                    <div class="text-xs opacity-70" style="color: var(--c-text);">Trò chuyện cùng cộng đồng</div>
                </div>
            </div>
            <span x-show="groupUnread > 0" x-text="groupUnread" class="px-2 py-0.5 rounded-full text-xs font-bold text-white" style="background: #dc3545;"></span>
    </div>

    <!-- Main Customer Support Chat Window Popup -->
    <div
        x-show="isOpen"
        x-cloak
        class="fixed absolute bottom-20 right-0 w-[calc(100vw-2rem)] sm:w-[380px] h-[540px] max-h-[80vh] sm:max-h-[600px] rounded-2xl shadow-2xl flex flex-col overflow-hidden border transition-all duration-300"
        style="background: #ffffff; border-color: var(--c-border); z-index: 1040;"
    >
        <!-- Header -->
        <div class="p-3.5 flex items-center justify-between flex-shrink-0 shadow-sm" style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: rgba(255,255,255,0.2);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm leading-tight mb-0">Hỗ trợ khách hàng</h4>
                    <p class="text-xs opacity-90 mb-0 flex items-center gap-1">
                        <span x-text="branchId ? ('Đang hỗ trợ bởi: ' + branchName) : (loadingLocation ? 'Đang xác định vị trí...' : 'Vui lòng chọn chi nhánh bên dưới')"></span>
                        <template x-if="branchId && !hasUserSentMessage">
                            <button type="button" @click.prevent="changeBranch()" class="ml-1 underline text-[11px] hover:opacity-100 opacity-90 font-semibold" style="color: #fef08a;">[Đổi chi nhánh]</button>
                        </template>
                    </p>
                </div>
            </div>
            <button type="button" @click.prevent="isOpen = false; localStorage.setItem('support_chat_open', 'false');" class="p-1 rounded-lg hover:bg-white/10 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages & Branch Selector Area (Solid Pure White Background) -->
        <div
            x-ref="messageList"
            class="flex-1 p-3 overflow-y-auto space-y-3"
            style="min-height: 0; background: #ffffff !important;"
        >
            <!-- Case: Unauthenticated User Prompt -->
            <template x-if="needLogin">
                <div class="flex flex-col items-center justify-center h-full gap-3 py-8 text-center px-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl" style="background: #fef3c7; color: #d97706;">
                        🔐
                    </div>
                    <div class="font-bold text-sm text-slate-800">Vui lòng đăng nhập</div>
                    <p class="text-xs text-slate-500 mb-2">Đăng nhập tài khoản để kết nối trực tiếp với nhân viên hỗ trợ chi nhánh.</p>
                    <a
                        href="{{ route('login') }}"
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-md active:scale-95"
                        style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); text-decoration: none;"
                    >
                        Đăng nhập ngay
                    </a>
                </div>
            </template>

            <!-- Loading state when fetching conversation -->
            <template x-if="!conversationId && !needLogin">
                <div class="flex flex-col items-center justify-center h-full gap-3 py-8">
                    <div class="spinner-border spinner-border-sm text-emerald-600" role="status" style="width: 28px; height: 28px;"></div>
                    <p class="text-xs text-slate-500 mb-0">Đang kết nối...</p>
                </div>
            </template>

            <!-- STEP 1 & 2: Sequential Location State Machine & Branch Selection Cards -->
            <template x-if="conversationId && !branchId && !needLogin">
                <div class="space-y-3">
                    <!-- Instant feedback toast when connecting to selected branch -->
                    <div x-show="selectingBranch" x-cloak class="p-3 rounded-xl shadow-md text-center border animate-pulse" style="background: #ecfaf6; color: #059669; border-color: #a7f3d0;">
                        <div class="font-bold text-xs">✔ Đã chọn <span x-text="selectedBranchNameTemp"></span></div>
                        <div class="text-[11px] mt-0.5">Đang kết nối với nhân viên hỗ trợ...</div>
                    </div>

                    <!-- STATE 1: Waiting for Browser Geolocation Permission Prompt -->
                    <div x-show="loadingLocation && locationState === 'prompt'" x-cloak class="p-4 rounded-2xl border text-center space-y-3" style="background: #f8fafc; border-color: #e2e8f0;">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mx-auto text-xl shadow-sm" style="background: #ecfaf6; color: #059669;">
                            📍
                        </div>
                        <div class="font-bold text-xs text-slate-800">Đang chờ bạn cho phép vị trí...</div>
                        <p class="text-[11px] text-slate-500 mb-0 leading-relaxed">
                            Bấm <b>[Cho phép]</b> trên thông báo trình duyệt ở góc trên màn hình để tìm 03 chi nhánh gần bạn nhất.
                        </p>
                        <div class="spinner-border spinner-border-sm text-emerald-600 mx-auto" role="status" style="width: 18px; height: 18px;"></div>
                    </div>

                    <!-- STATE 2: Location Granted -> Fetching Nearest Branches Loading State -->
                    <div x-show="loadingLocation && locationState === 'granted'" x-cloak class="p-4 rounded-2xl border text-center space-y-2" style="background: #f8fafc; border-color: #e2e8f0;">
                        <div class="spinner-border spinner-border-sm text-emerald-600 mx-auto" role="status" style="width: 22px; height: 22px;"></div>
                        <div class="text-xs font-semibold text-slate-700">Đang tìm 03 chi nhánh gần bạn nhất...</div>
                    </div>

                    <!-- STATE 3a: Location Denied - show message, no branch list -->
                    <template x-if="!loadingLocation && locationState === 'denied'">
                        <div class="flex flex-col items-center justify-center h-full gap-3 py-10 px-4 text-center">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl" style="background: #fff7ed; color: #ea580c;">
                                📍
                            </div>
                            <div class="font-bold text-sm text-slate-800">Không thể xác định vị trí</div>
                            <p class="text-xs text-slate-500 mb-3 leading-relaxed">
                                Vui lòng cho phép truy cập vị trí trong cài đặt trình duyệt rồi thử lại để chúng tôi tìm chi nhánh gần bạn nhất.
                            </p>
                            <button
                                type="button"
                                @click.prevent="requestLocationAndFetchBranches()"
                                class="px-5 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-sm active:scale-95"
                                style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent));"
                            >
                                🔄 Thử lại
                            </button>
                        </div>
                    </template>

                    <!-- STATE 3b: Location Granted -> Branch cards -->
                    <template x-if="!loadingLocation && locationState !== 'denied'">
                        <div class="space-y-3">
                            <!-- Welcome Card -->
                            <div class="p-3.5 rounded-2xl shadow-sm border space-y-1" style="background: #f8fafc; border-color: #e2e8f0;">
                                <div class="font-bold text-emerald-700 text-sm">Xin chào! 👋</div>
                                <p class="text-xs text-slate-600 mb-0 leading-relaxed">
                                    Chúng tôi đã tìm thấy 03 chi nhánh gần bạn nhất. Vui lòng chọn một chi nhánh để bắt đầu trò chuyện.
                                </p>
                            </div>

                            <!-- 3 Branch Selection Cards -->
                            <div class="space-y-2 pt-1">
                                <div class="text-xs font-bold text-slate-700 px-1">
                                    Chi nhánh gần bạn:
                                </div>

                                <template x-for="branch in nearestBranches" :key="branch.id">
                                    <div class="p-3 rounded-2xl shadow-sm border transition-all hover:border-emerald-500" style="background: #ffffff; border-color: #e2e8f0;">
                                        <div class="flex items-start justify-between gap-2 mb-1">
                                            <div class="font-bold text-sm text-slate-900" x-text="'📍 ' + branch.name"></div>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap"
                                                style="background: #ecfaf6; color: #059669; border: 1px solid #a7f3d0;"
                                                x-text="branch.distance_text"
                                            ></span>
                                        </div>
                                        <div class="text-xs text-slate-500 mb-2 leading-tight" x-text="branch.address"></div>

                                        <button
                                            type="button"
                                            @click.prevent="selectBranch(branch)"
                                            :disabled="selectingBranch"
                                            class="w-full py-2 rounded-xl text-xs font-bold text-white flex items-center justify-center gap-1.5 transition-all shadow-sm active:scale-95"
                                            style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent));"
                                        >
                                            <span x-show="!selectingBranch || selectedBranchNameTemp !== branch.name">Kết nối ngay</span>
                                            <span x-show="selectingBranch && selectedBranchNameTemp === branch.name" class="spinner-border spinner-border-sm" role="status"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- STEP 3: Connected Chat Message Stream -->
            <template x-if="conversationId && branchId && !needLogin">
                <div class="space-y-3">
                    <!-- Message list -->
                    <template x-for="message in messages" :key="message.id">
                        <div
                            :class="[
                                'flex w-full mb-2',
                                message.sender_id == currentUserId ? 'justify-end' : 'justify-start'
                            ]"
                        >
                            <div
                                :class="[
                                    'max-w-[85%] rounded-2xl px-3.5 py-2.5 shadow-sm text-sm break-words',
                                    message.sender_id == currentUserId ? 'rounded-tr-none' : 'rounded-tl-none'
                                ]"
                                :style="message.sender_id == currentUserId ? 'background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;' : (message.content && message.content.includes('🤖 Hệ thống') ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0;')"
                            >
                                <div x-text="message.content" x-show="message.content" class="mb-1" style="white-space: pre-line;"></div>
                                <div
                                    x-text="message.created_at ? new Date(message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false }) : ''"
                                    class="text-[11px] opacity-70"
                                    :title="message.created_at ? new Date(message.created_at).toLocaleString('vi-VN') : ''"
                                ></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Input Area (Only visible AFTER branch is selected & authenticated) -->
        <template x-if="conversationId && branchId && !needLogin">
            <div class="p-3 border-t flex-shrink-0" style="flex: 0 0 auto; background: #ffffff; border-color: #e2e8f0;">
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        x-model="newMessage"
                        @keydown.enter.prevent="sendMessage()"
                        :disabled="loading"
                        placeholder="Nhập câu hỏi hoặc yêu cầu của bạn..."
                        class="flex-1 px-3 py-2 rounded-xl text-sm focus:outline-none transition-all disabled:opacity-60"
                        style="background: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a;"
                    >
                    <button
                        type="button"
                        @click.prevent="sendMessage()"
                        :disabled="loading || !newMessage.trim()"
                        class="p-2 rounded-xl transition-all hover:opacity-90 disabled:opacity-50"
                        style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
