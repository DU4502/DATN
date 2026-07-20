<div
    x-data="{
        isOpen: false,
        menuOpen: false,
        groupChatAvailable: false,
        groupChatOpen: false,
        groupUnread: 0,
        supportUnread: 0,
        conversationId: null,
        branchId: null,
        branchName: '',
        conversationStatus: 'open',
        messages: [],
        newMessage: '',
        loading: false,
        loadingBranches: false,
        branches: [],
        gpsDenied: false,
        gpsErrorMessage: '',
        showEndSessionModal: false,
        endingSession: false,
        pollInterval: null,
        unreadPollInterval: null,
        echoChannel: null,
        visibilityHandler: null,

        get branchNameDisplay() {
            if (!this.branchName) return '';
            return this.branchName.startsWith('Chi nhánh') ? this.branchName : 'Chi nhánh ' + this.branchName;
        },

        async init() {
            this.groupChatAvailable = Boolean(document.querySelector('[data-vue-group-chat]'));
            window.addEventListener('group-chat-unread', (event) => {
                this.groupUnread = Number(event.detail?.count || 0);
            });
            window.addEventListener('group-chat-opened', () => {
                this.isOpen = false;
                this.menuOpen = false;
                this.groupChatOpen = true;
            });
            window.addEventListener('group-chat-closed', () => {
                this.groupChatOpen = false;
            });
            window.addEventListener('support-chat-close', () => {
                this.isOpen = false;
                this.menuOpen = false;
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
                if (isOpen) {
                    this.stopUnreadPolling();
                    this.activateSupportChat();
                } else {
                    this.stopPolling();
                    this.startUnreadPolling();
                }
            });

            await this.fetchUnreadCount();
            this.subscribeEchoChannel();
            this.startUnreadPolling();
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
                return;
            }
            window.dispatchEvent(new CustomEvent('group-chat-close'));
            this.isOpen = true;
        },

        async openGroupChat() {
            this.menuOpen = false;
            this.isOpen = false;
            window.dispatchEvent(new CustomEvent('group-chat-toggle'));
        },

        async toggleUnifiedChat() {
            if (this.isOpen) {
                this.isOpen = false;
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
            if (document.hidden || !this.isOpen) return;
            if (!this.conversationId) {
                await this.getOrCreateConversation();
            }
            if (!this.conversationId || !this.isOpen) return;

            if (!this.branchId) {
                await this.requestGpsLocation();
            } else {
                await this.fetchMessages(true);
            }

            this.supportUnread = 0;
            this.subscribeEchoChannel();
            this.startPolling();
        },

        async requestGpsLocation() {
            this.loadingBranches = true;
            this.gpsDenied = true;
            this.gpsErrorMessage = 'Không thể xác định vị trí của bạn. Vui lòng kiểm tra lại thiết bị hoặc bấm nút thử lại.';
            this.branches = [];

            if (!navigator.geolocation) {
                this.loadingBranches = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    await this.loadNearestBranches(lat, lng);
                    this.gpsDenied = false;
                    this.loadingBranches = false;
                },
                async (error) => {
                    console.warn('GPS position error:', error);
                    this.gpsDenied = true;
                    this.branches = [];
                    this.loadingBranches = false;
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 }
            );
        },

        async loadNearestBranches(lat, lng) {
            if (lat === null || lng === null) return;
            try {
                const url = `{{ route('chat.nearest-branches') }}?lat=${lat}&lng=${lng}`;
                const res = await fetch(url);
                const data = await res.json();
                if (data.success && data.branches && data.branches.length > 0) {
                    this.branches = data.branches;
                    this.gpsDenied = false;
                }
            } catch (e) {
                console.error('Error loading nearest branches:', e);
            }
        },

        async selectBranchItem(branchId) {
            if (!this.conversationId || !branchId) return;
            this.loading = true;
            try {
                const res = await fetch('{{ route('chat.select-branch') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.conversationId,
                        branch_id: branchId,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.branchId = data.branch_id;
                    this.branchName = data.branch_name;
                    await this.fetchMessages(true);
                    this.subscribeEchoChannel();
                } else {
                    alert(data.message || 'Không thể chọn chi nhánh. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('Select branch error:', e);
            } finally {
                this.loading = false;
            }
        },

        async openEndSessionModal() {
            this.showEndSessionModal = true;
        },

        async confirmEndSession() {
            if (!this.conversationId) return;
            this.endingSession = true;
            try {
                const res = await fetch('{{ route('chat.end-session') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.conversationId,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.leaveEchoChannel();
                    this.showEndSessionModal = false;
                    this.branchId = null;
                    this.branchName = '';
                    this.conversationId = null;
                    this.messages = [];

                    await this.getOrCreateConversation();
                    await this.requestGpsLocation();
                }
            } catch (e) {
                console.error('End session error:', e);
            } finally {
                this.endingSession = false;
            }
        },

        subscribeEchoChannel() {
            if (!window.Echo || !this.conversationId) return;
            if (this.echoChannel) return;

            this.echoChannel = window.Echo.private('conversation.' + this.conversationId)
                .listen('.message-sent', (payload) => {
                    const alreadyExists = this.messages.some(m => m.id === payload.message_id);
                    if (alreadyExists) return;

                    const isFromAdmin = payload.sender_id !== {{ auth()->id() ?? 0 }};

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

                    if (!this.isOpen && isFromAdmin) {
                        this.supportUnread++;
                    }

                    if (this.isOpen) {
                        this.$nextTick(() => { this.scrollToBottom(); });
                    }
                })
                .listen('.conversation-closed', (payload) => {
                    this.conversationStatus = 'closed';
                    this.leaveEchoChannel();
                    this.branchId = null;
                    this.branchName = '';
                    this.conversationId = null;
                    this.messages = [];
                    if (this.isOpen) {
                        alert('Phiên làm việc hiện tại đã kết thúc. Vui lòng chọn chi nhánh để mở phiên mới.');
                        this.getOrCreateConversation().then(() => this.requestGpsLocation());
                    }
                })
                .error((error) => {
                    console.warn('Echo channel error, using fallback polling', error);
                    this.echoChannel = null;
                    if (this.isOpen) this.startPolling();
                });
        },

        async fetchUnreadCount() {
            if (!this.conversationId) {
                try {
                    const res = await fetch('{{ route('chat.index') }}');
                    const data = await res.json();
                    if (data.success) {
                        this.conversationId = data.conversation_id;
                        this.branchId       = data.branch_id;
                        this.branchName     = data.branch_name || '';
                        this.conversationStatus = data.status || 'open';
                        this.subscribeEchoChannel();
                    }
                } catch (e) { return; }
            }
            if (!this.conversationId) return;
            try {
                const res  = await fetch('{{ route('chat.messages') }}?conversation_id=' + this.conversationId);
                const data = await res.json();
                if (data.success) {
                    const uid = {{ auth()->id() ?? 0 }};
                    this.supportUnread = data.messages.filter(m => m.sender_id !== uid && !m.is_read).length;
                }
            } catch (e) {}
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
            if (!this.isOpen || document.hidden || !this.conversationId) return;
            this.pollInterval = window.setInterval(() => {
                if (this.isOpen && !document.hidden) this.fetchMessages();
            }, 1500);
        },

        stopPolling() {
            if (!this.pollInterval) return;
            window.clearInterval(this.pollInterval);
            this.pollInterval = null;
        },

        async getOrCreateConversation() {
            try {
                const res = await fetch('{{ route('chat.index') }}');
                const data = await res.json();
                if (data.success) {
                    this.conversationId = data.conversation_id;
                    this.branchId       = data.branch_id;
                    this.branchName     = data.branch_name || '';
                    this.conversationStatus = data.status || 'open';
                }
            } catch (e) {
                console.error('Error getting conversation', e);
            }
        },

        async fetchMessages(markRead = false) {
            try {
                const url = '{{ route('chat.messages') }}?conversation_id=' + this.conversationId
                    + (markRead ? '&mark_as_read=1' : '');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    if (data.conversation_status) {
                        this.conversationStatus = data.conversation_status;
                    }
                    const lastLocalId  = this.messages.length ? this.messages[this.messages.length - 1].id : null;
                    const lastServerId = data.messages.length ? data.messages[data.messages.length - 1].id : null;
                    if (lastServerId !== lastLocalId || data.messages.length !== this.messages.length) {
                        this.messages = data.messages;
                        this.$nextTick(() => { this.scrollToBottom(); });
                    }
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
            if (!this.newMessage.trim() || !this.conversationId) return;

            const text = this.newMessage.trim();
            this.newMessage = '';

            const tempId = 'tmp_' + Date.now();
            const tempMsg = {
                id: tempId,
                conversation_id: this.conversationId,
                sender_id: {{ auth()->id() ?? 0 }},
                content: text,
                attachment_path: null,
                attachment_name: null,
                is_read: false,
                created_at: new Date().toISOString(),
                sender: { id: {{ auth()->id() ?? 0 }}, name: '{{ addslashes(auth()->user()->name ?? 'Khách hàng') }}' },
                _pending: true,
            };
            this.messages.push(tempMsg);
            this.$nextTick(() => { this.scrollToBottom(); });

            this.loading = true;
            const formData = new FormData();
            formData.append('conversation_id', this.conversationId);
            formData.append('content', text);

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
                    const idx = this.messages.findIndex(m => m.id === tempId);
                    if (idx !== -1) this.messages.splice(idx, 1, data.message);
                } else {
                    this.messages = this.messages.filter(m => m.id !== tempId);
                    this.newMessage = text;
                    alert(data.message || 'Không thể gửi tin nhắn. Vui lòng thử lại.');
                }
            } catch (e) {
                this.messages = this.messages.filter(m => m.id !== tempId);
                this.newMessage = text;
                alert('Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.');
            } finally {
                this.loading = false;
            }
        },
    }"
    class="fixed bottom-6 right-6 z-50" style="position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1050;"
>
    <!-- Custom Scrollbar Styles for Chatbox -->
    <style>
        .chatbox-body-scroll {
            flex: 1 1 0% !important;
            min-height: 0 !important;
            height: 100% !important;
            max-height: 100% !important;
            overflow-y: auto !important;
            overscroll-behavior: contain !important;
            -webkit-overflow-scrolling: touch !important;
        }
        .chatbox-body-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .chatbox-body-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .chatbox-body-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .chatbox-body-scroll::-webkit-scrollbar-thumb:hover {
            background: #00a870;
        }
    </style>

    <!-- Floating Toggle Button -->
    <button
        @click="toggleUnifiedChat()"
        class="flex items-center justify-center w-16 h-16 rounded-full shadow-xl transition-all duration-300 hover:scale-110"
        style="position:relative; background: #00a870;"
    >
        <svg x-show="!isOpen && !groupChatOpen" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg x-show="isOpen || groupChatOpen" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span x-show="groupUnread > 0 && supportUnread === 0" x-text="groupUnread > 99 ? '99+' : groupUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        <span x-show="supportUnread > 0" x-text="supportUnread > 99 ? '99+' : supportUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
    </button>

    <!-- Unified Menu -->
    <div x-show="menuOpen && groupChatAvailable" x-transition style="position:absolute;right:0;bottom:5rem;width:230px;padding:.55rem;border-radius:16px;background:#fff;border:1px solid #e1ebe8;box-shadow:0 18px 48px rgba(7,52,58,.2);display:grid;gap:.4rem;">
        <button type="button" @click="openGroupChat()" style="display:flex;align-items:center;gap:.7rem;width:100%;padding:.75rem;border:0;border-radius:12px;background:#f1f0ff;color:#4f46c8;font-weight:800;text-align:left;">
            <span style="width:36px;height:36px;border-radius:50%;background:#5b50d6;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="bi bi-people-fill"></i></span>
            <span style="flex:1;">Trò chuyện trong đơn nhóm</span>
            <span x-show="groupUnread > 0" x-text="groupUnread" class="badge rounded-pill bg-danger"></span>
        </button>
        <button type="button" @click="openSupportChat()" style="display:flex;align-items:center;gap:.7rem;width:100%;padding:.75rem;border:0;border-radius:12px;background:#ecfaf6;color:#087c63;font-weight:800;text-align:left;">
            <span style="width:36px;height:36px;border-radius:50%;background:#00a870;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="bi bi-headset"></i></span>
            <span style="flex:1;">Hỗ trợ khách hàng</span>
            <span x-show="supportUnread > 0" x-text="supportUnread > 99 ? '99+' : supportUnread" class="badge rounded-pill bg-danger"></span>
        </button>
    </div>

    <!-- Chat Window Card -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        style="position: absolute; right: 0; bottom: 5rem; width: 23rem; max-width: calc(100vw - 2rem); height: min(520px, calc(100vh - 7rem)); max-height: calc(100vh - 7rem); display: flex; flex-direction: column; overflow: hidden; background: #ffffff; border-radius: 1.25rem; box-shadow: 0 20px 40px rgba(0,0,0,0.18);"
    >
        <!-- Header Green (Giống ảnh 1 & 3) -->
        <div style="padding: 0.85rem 1rem; background: #00a870; color: white; flex: 0 0 auto;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: rgba(255,255,255,0.22); display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: white;">Hỗ trợ khách hàng</h3>
                        <p x-show="branchId" style="margin: 0; font-size: 0.72rem; color: rgba(255,255,255,0.85);">
                            Đang hỗ trợ bởi: <span x-text="branchNameDisplay"></span>
                            <button
                                @click="openEndSessionModal()"
                                type="button"
                                style="background: none; border: none; padding: 0; margin-left: 0.4rem; color: #ffffff; text-decoration: underline; font-weight: 700; cursor: pointer;"
                            >[Đổi chi nhánh]</button>
                        </p>
                        <p x-show="!branchId" style="margin: 0; font-size: 0.72rem; color: rgba(255,255,255,0.85);">
                            Vui lòng chọn chi nhánh bên dưới
                        </p>
                    </div>
                </div>
                <button @click="isOpen = false" style="background: none; border: 0; color: white; cursor: pointer; padding: 0.2rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- STATE 1: Màn hình chọn Chi nhánh (Khi chưa có branchId) -->
        <div
            x-show="!branchId"
            class="chatbox-body-scroll"
            style="padding: 0.9rem; background: #f8faf9; display: flex; flex-direction: column; gap: 0.8rem;"
        >
            <!-- Card banner xin chào -->
            <div style="background: #ffffff; border: 1px solid #e2ece9; border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <h4 style="margin: 0 0 0.4rem 0; font-size: 0.95rem; font-weight: 700; color: #0d684d;">Xin chào! 👋</h4>
                <p style="margin: 0; font-size: 0.82rem; color: #4b5563; line-height: 1.45;">
                    Chúng tôi cần vị trí GPS của bạn để tìm 03 chi nhánh gần nhất. Vui lòng cấp quyền vị trí trên trình duyệt.
                </p>
            </div>

            <!-- Cảnh báo khi chưa chia sẻ / từ chối GPS -->
            <template x-if="gpsDenied && (!branches || branches.length === 0)">
                <div style="background: #fff8f8; border: 1px solid #fecaca; border-radius: 1rem; padding: 1rem; text-align: center;">
                    <div style="color: #dc2626; font-size: 1.6rem; margin-bottom: 0.3rem;"><i class="bi bi-geo-alt-fill"></i></div>
                    <h5 style="margin: 0 0 0.3rem 0; font-size: 0.88rem; font-weight: 700; color: #991b1b;">Cần vị trí GPS</h5>
                    <p style="margin: 0 0 0.8rem 0; font-size: 0.78rem; color: #7f1d1d; line-height: 1.4;" x-text="gpsErrorMessage"></p>
                    <button
                        @click="requestGpsLocation()"
                        type="button"
                        style="background: #00a870; color: white; border: none; border-radius: 0.6rem; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 700; cursor: pointer;"
                    >
                        Thử lại lấy vị trí GPS
                    </button>
                </div>
            </template>

            <!-- Loading Spinner khi đang chờ bấm cho phép GPS -->
            <template x-if="loadingBranches && (!branches || branches.length === 0)">
                <div style="text-align: center; padding: 1rem 0; color: #6b7280;">
                    <div class="spinner-border spinner-border-sm mb-2" role="status" style="color: #00a870;"></div>
                    <p style="font-size: 0.8rem; margin: 0;">Đang chờ bạn bấm "Cho phép" chia sẻ vị trí trên trình duyệt...</p>
                </div>
            </template>

            <!-- Danh sách 3 chi nhánh (Chỉ hiện ra KHI ĐÃ CẤP QUYỀN VÀ NẠP THÀNH CÔNG KHÔNG ẨN THẺ CẢNH BÁO) -->
            <template x-if="branches && branches.length > 0">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #374151;">Chi nhánh gần bạn:</span>
                    
                    <template x-for="b in branches" :key="b.id">
                        <div style="background: #ffffff; border: 1px solid #e1ebe8; border-radius: 1rem; padding: 0.85rem; display: flex; flex-direction: column; gap: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.4rem;">
                                    <span style="color: #dc2626; font-size: 0.9rem; line-height: 1.3;"><i class="bi bi-geo-alt-fill"></i></span>
                                    <div>
                                        <h5 style="margin: 0; font-size: 0.9rem; font-weight: 700; color: #111827;" x-text="b.name"></h5>
                                        <p style="margin: 0.2rem 0 0 0; font-size: 0.75rem; color: #6b7280; line-height: 1.35;" x-text="b.address"></p>
                                    </div>
                                </div>
                                <span x-show="b.distance_text" style="background: #e6f7f2; color: #00a870; font-weight: 700; font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 999px; white-space: nowrap;" x-text="b.distance_text"></span>
                            </div>
                            <button
                                @click="selectBranchItem(b.id)"
                                type="button"
                                style="width: 100%; background: #00a870; color: white; border: none; border-radius: 0.6rem; padding: 0.5rem; font-weight: 700; font-size: 0.82rem; cursor: pointer; transition: background 0.2s;"
                            >
                                Kết nối ngay
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- STATE 2: Cửa sổ Chat Active (Khi đã chọn branchId) -->
        <div
            x-show="branchId"
            style="flex: 1 1 0%; min-height: 0; height: 100%; display: flex; flex-direction: column; overflow: hidden;"
        >
            <!-- List tin nhắn -->
            <div
                x-ref="messageList"
                class="chatbox-body-scroll"
                style="padding: 0.9rem; background: #ffffff; display: flex; flex-direction: column; gap: 0.75rem;"
            >
                <template x-for="message in messages" :key="message.id">
                    <div
                        :style="message.sender_id == {{ auth()->id() ?? 0 }} ? 'display: flex; justify-content: flex-end;' : 'display: flex; justify-content: flex-start;'"
                    >
                        <!-- Tin nhắn từ Bot hệ thống -->
                        <template x-if="message.content && message.content.startsWith('🤖 Hệ thống')">
                            <div style="max-width: 90%; background: #edf9f5; border: 1px solid #c3ebd9; border-radius: 1rem; padding: 0.75rem 0.85rem; color: #0d684d; font-size: 0.82rem; line-height: 1.45;">
                                <div style="font-weight: 700; color: #00a870; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.3rem;">
                                    <span>🤖</span> Hệ thống
                                </div>
                                <div x-text="message.content.replace('🤖 Hệ thống\n', '').replace('🤖 Hệ thống', '')" style="white-space: pre-line;"></div>
                                <div
                                    x-text="new Date(message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })"
                                    style="font-size: 0.68rem; opacity: 0.65; text-align: left; margin-top: 0.3rem;"
                                ></div>
                            </div>
                        </template>

                        <!-- Tin nhắn thường (User hoặc Admin/CSKH) -->
                        <template x-if="!message.content || !message.content.startsWith('🤖 Hệ thống')">
                            <div
                                :style="message.sender_id == {{ auth()->id() ?? 0 }} 
                                    ? 'max-w-[82%]; background: #00a870; color: white; border-radius: 1rem 1rem 0 1rem; padding: 0.6rem 0.8rem; font-size: 0.83rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' 
                                    : 'max-w-[82%]; background: #f1f5f9; color: #1e293b; border-radius: 1rem 1rem 1rem 0; padding: 0.6rem 0.8rem; font-size: 0.83rem; border: 1px solid #e2e8f0;'"
                            >
                                <div x-text="message.content" x-show="message.content" style="white-space: pre-line;"></div>
                                <div
                                    x-text="new Date(message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })"
                                    :style="message.sender_id == {{ auth()->id() ?? 0 }} ? 'font-size: 0.68rem; opacity: 0.8; text-align: right; margin-top: 0.25rem;' : 'font-size: 0.68rem; opacity: 0.6; text-align: left; margin-top: 0.25rem;'"
                                ></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Input Chat -->
            <div style="padding: 0.75rem 0.85rem; border-top: 1px solid #e2e8f0; background: #ffffff; flex: 0 0 auto;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input
                        type="text"
                        x-model="newMessage"
                        @keydown.enter.prevent="sendMessage()"
                        :disabled="loading"
                        placeholder="Nhập câu hỏi hoặc yêu cầu của bạn..."
                        style="flex: 1; padding: 0.6rem 0.85rem; border-radius: 1.25rem; border: 1px solid #cbd5e1; font-size: 0.82rem; outline: none; background: #f8fafc;"
                    >
                    <button
                        @click="sendMessage()"
                        :disabled="loading || !newMessage.trim()"
                        style="width: 2.3rem; height: 2.3rem; border-radius: 50%; background: #00a870; border: none; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal toàn màn hình khi bấm Kết thúc phiên / Đổi chi nhánh -->
    <div
        x-show="showEndSessionModal"
        x-transition
        style="position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 1rem;"
    >
        <div style="background: white; border-radius: 1.25rem; padding: 1.5rem; width: 100%; max-width: 20rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); text-align: center;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 50%; background: #edf9f5; color: #00a870; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem auto; font-size: 1.75rem;">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <h4 style="margin: 0 0 0.5rem 0; font-size: 1.05rem; font-weight: 700; color: #111827;">Xác nhận kết thúc phiên?</h4>
            <p style="margin: 0 0 1.25rem 0; font-size: 0.83rem; color: #4b5563; line-height: 1.5;">
                Phiên làm việc với <strong style="color: #00a870;" x-text="branchNameDisplay"></strong> sẽ được khép lại để bạn chọn chi nhánh khác. Bạn có chắc chắn muốn kết thúc không?
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button
                    @click="confirmEndSession()"
                    :disabled="endingSession"
                    type="button"
                    style="width: 100%; background: #dc2626; color: white; border: none; border-radius: 0.75rem; padding: 0.65rem; font-size: 0.85rem; font-weight: 700; cursor: pointer;"
                >
                    Kết thúc & Chọn chi nhánh mới
                </button>
                <button
                    @click="showEndSessionModal = false"
                    type="button"
                    style="width: 100%; background: #f3f4f6; color: #374151; border: none; border-radius: 0.75rem; padding: 0.6rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;"
                >
                    Hủy bỏ (Tiếp tục chat)
                </button>
            </div>
        </div>
    </div>
</div>
