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
        messages: [],
        newMessage: '',
        loading: false,
        pollInterval: null,
        unreadPollInterval: null,
        echoChannel: null,
        visibilityHandler: null,

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
                    this.activateSupportChat(); // reset unread CHỈ sau khi đã fetch + mark read
                } else {
                    this.stopPolling();
                    // KHÔNG leaveEchoChannel — giữ subscribe để nhận unread realtime
                    this.startUnreadPolling();
                }
            });

            // Lấy conversationId + unread count ngay khi load trang
            await this.fetchUnreadCount();
            // Subscribe WebSocket ngay lập tức (dù chat đóng)
            this.subscribeEchoChannel();
            // Poll backup 20s nếu Reverb không hoạt động
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

            // Luôn gọi getOrCreateConversation — backend tự xác định branch
            if (!this.conversationId) {
                await this.getOrCreateConversation();
            }
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
            if (!this.conversationId) await this.getOrCreateConversation();
            if (!this.conversationId || !this.isOpen) return;
            await this.fetchMessages(true); // mark as read trước, rồi mới reset badge
            this.supportUnread = 0;
            // subscribeEchoChannel đã được gọi từ init, chỉ gọi lại nếu chưa có
            this.subscribeEchoChannel();
            this.startPolling();
        },

        subscribeEchoChannel() {
            if (!window.Echo || !this.conversationId) return;
            // Tránh subscribe trùng
            if (this.echoChannel) return;

            this.echoChannel = window.Echo.private('conversation.' + this.conversationId)
                .listen('.message-sent', (payload) => {
                    // Bỏ qua tin nhắn đã có
                    const alreadyExists = this.messages.some(m => m.id === payload.message_id);
                    if (alreadyExists) return;

                    // Tin từ admin/CSKH (không phải user đang đăng nhập)
                    const isFromAdmin = payload.sender_id !== <?php echo e(auth()->id()); ?>;

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
                })
                .error((error) => {
                    // WebSocket auth thất bại → tăng tần suất polling làm fallback
                    console.warn('Echo channel error, switching to fast polling', error);
                    this.echoChannel = null;
                    if (this.isOpen) this.startPolling();
                });
        },

        // Fetch unread count từ server (fallback khi Reverb chưa kết nối)
        async fetchUnreadCount() {
            if (!this.conversationId) {
                try {
                    const res = await fetch('<?php echo e(route('chat.index')); ?>');
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
                const res  = await fetch('<?php echo e(route('chat.messages')); ?>?conversation_id=' + this.conversationId);
                const data = await res.json();
                if (data.success) {
                    const uid = <?php echo e(auth()->id()); ?>;
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
            if (!this.isOpen || document.hidden || !this.conversationId) return;
            // Polling 100ms fallback khi Reverb không hoạt động
            this.pollInterval = window.setInterval(() => {
                if (this.isOpen && !document.hidden) this.fetchMessages();
            }, 100);
        },

        stopPolling() {
            if (!this.pollInterval) return;
            window.clearInterval(this.pollInterval);
            this.pollInterval = null;
        },

        async getOrCreateConversation() {
            try {
                const res = await fetch('<?php echo e(route('chat.index')); ?>');
                const data = await res.json();
                if (data.success) {
                    this.conversationId = data.conversation_id;
                    this.branchId = data.branch_id;
                    this.branchName = data.branch_name || '';
                }
            } catch (e) {
                console.error('Error getting conversation', e);
            }
        },

        async selectBranch(branch) {
            // Không còn dùng - branch được tự động xác định từ server
        },

        async fetchMessages(markRead = false) {
            try {
                const url = '<?php echo e(route('chat.messages')); ?>?conversation_id=' + this.conversationId
                    + (markRead ? '&mark_as_read=1' : '');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    // So sánh ID tin nhắn cuối để tránh re-render không cần thiết
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

            // Optimistic UI: hiện tin ngay lập tức không chờ server
            const tempId = 'tmp_' + Date.now();
            const tempMsg = {
                id: tempId,
                conversation_id: this.conversationId,
                sender_id: <?php echo e(auth()->id()); ?>,
                content: text,
                attachment_path: null,
                attachment_name: null,
                is_read: false,
                created_at: new Date().toISOString(),
                sender: { id: <?php echo e(auth()->id()); ?>, name: '<?php echo e(addslashes(auth()->user()->name)); ?>' },
                _pending: true,
            };
            this.messages.push(tempMsg);
            this.$nextTick(() => { this.scrollToBottom(); });

            this.loading = true;
            const formData = new FormData();
            formData.append('conversation_id', this.conversationId);
            formData.append('content', text);

            try {
                const res = await fetch('<?php echo e(route('chat.send')); ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    // Thay tin tạm bằng tin thật từ server
                    const idx = this.messages.findIndex(m => m.id === tempId);
                    if (idx !== -1) this.messages.splice(idx, 1, data.message);
                } else {
                    // Xóa tin tạm nếu server báo lỗi
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
    <!-- Toggle button -->
    <button
        @click="toggleUnifiedChat()"
        class="flex items-center justify-center w-16 h-16 rounded-full shadow-xl transition-all duration-300 hover:scale-110"
        style="position:relative;background: linear-gradient(135deg, var(--c-primary), var(--c-accent));"
    >
        <svg x-show="!isOpen && !groupChatOpen" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg x-show="isOpen || groupChatOpen" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        
        <span x-show="groupUnread > 0 && supportUnread === 0" x-text="groupUnread > 99 ? '99+' : groupUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        
        <span x-show="supportUnread > 0" x-text="supportUnread > 99 ? '99+' : supportUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        
        <template x-if="groupUnread > 0 && supportUnread > 0">
            <span style="position:absolute;bottom:-4px;left:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#5b50d6;color:#fff;border:2px solid #fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;" x-text="groupUnread > 9 ? '9+' : groupUnread"></span>
        </template>
    </button>

    <div x-show="menuOpen && groupChatAvailable" x-transition style="position:absolute;right:0;bottom:5rem;width:230px;padding:.55rem;border-radius:16px;background:#fff;border:1px solid #e1ebe8;box-shadow:0 18px 48px rgba(7,52,58,.2);display:grid;gap:.4rem;">
        <button type="button" @click="openGroupChat()" style="display:flex;align-items:center;gap:.7rem;width:100%;padding:.75rem;border:0;border-radius:12px;background:#f1f0ff;color:#4f46c8;font-weight:800;text-align:left;">
            <span style="width:36px;height:36px;border-radius:50%;background:#5b50d6;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="bi bi-people-fill"></i></span>
            <span style="flex:1;">Trò chuyện trong đơn nhóm</span>
            <span x-show="groupUnread > 0" x-text="groupUnread" class="badge rounded-pill bg-danger"></span>
        </button>
        <button type="button" @click="openSupportChat()" style="display:flex;align-items:center;gap:.7rem;width:100%;padding:.75rem;border:0;border-radius:12px;background:#ecfaf6;color:#087c63;font-weight:800;text-align:left;">
            <span style="width:36px;height:36px;border-radius:50%;background:var(--c-primary);color:#fff;display:flex;align-items:center;justify-content:center;"><i class="bi bi-headset"></i></span>
            <span style="flex:1;">Hỗ trợ khách hàng</span>
            <span x-show="supportUnread > 0" x-text="supportUnread > 99 ? '99+' : supportUnread" class="badge rounded-pill bg-danger"></span>
        </button>
    </div>

    <!-- Chat window -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="absolute bottom-20 right-0 w-80 max-w-[85vw] rounded-2xl shadow-2xl overflow-hidden flex flex-col" style="position: absolute; right: 0; bottom: 5rem; width: 22rem; max-width: calc(100vw - 2rem); height: min(480px, calc(100vh - 7rem)); max-height: calc(100vh - 7rem); display: flex; flex-direction: column; overflow: hidden; background: var(--c-surface);"
    >
        <!-- Header -->
        <div class="p-3 border-b flex-shrink-0" style="flex: 0 0 auto; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); border-color: var(--c-border);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-sm mb-0">Hỗ trợ khách hàng</h3>
                        <p x-show="branchName" class="text-white/80 text-xs m-0" x-text="'Chi nhánh: ' + branchName" style="font-size: 11px; color: rgba(255,255,255,0.85);"></p>
                    </div>
                </div>
                <button @click="isOpen = false" class="text-white/80 hover:text-white" style="background: none; border: 0; color: white;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div
            x-ref="messageList"
            class="flex-1 p-3 overflow-y-auto space-y-3"
            style="min-height: 0; background: var(--c-background);"
        >
            <!-- Loading state khi đang kết nối -->
            <template x-if="!conversationId">
                <div class="flex flex-col items-center justify-center h-full gap-3 py-8">
                    <div class="spinner-border spinner-border-sm" role="status" style="color: var(--c-primary); width: 28px; height: 28px;"></div>
                    <p class="text-xs opacity-60 mb-0" style="color: var(--c-text);">Đang kết nối tới nhân viên hỗ trợ...</p>
                </div>
            </template>

            <!-- Messages stream -->
            <template x-if="conversationId">
                <div>
                    <template x-for="message in messages" :key="message.id">
                        <div
                            :class="[
                                'flex w-full mb-2',
                                message.sender_id == <?php echo e(auth()->id()); ?> ? 'justify-end' : 'justify-start'
                            ]"
                        >
                            <div
                                :class="[
                                    'max-w-[85%] rounded-xl px-3 py-2 shadow-sm text-sm break-words',
                                    message.sender_id == <?php echo e(auth()->id()); ?> ? 'rounded-tr-none' : 'rounded-tl-none'
                                ]"
                            :style="message.sender_id == <?php echo e(auth()->id()); ?> ? 'background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;' + (message._pending ? 'opacity:0.7;' : '') : 'background: var(--c-surface); color: var(--c-text); border: 1px solid var(--c-border);'"
                            >
                                <div x-text="message.content" x-show="message.content" class="mb-1" style="white-space: pre-line;"></div>
                                <div
                                    x-text="new Date(message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })"
                                    class="text-xs opacity-70"
                                    :title="new Date(message.created_at).toLocaleString('vi-VN')"
                                ></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Input -->
        <div class="p-3 border-t flex-shrink-0" style="flex: 0 0 auto; background: var(--c-surface); border-color: var(--c-border);">
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    x-model="newMessage"
                    @keydown.enter.prevent="sendMessage()"
                    :disabled="!conversationId"
                    placeholder="Nhập tin nhắn..."
                    class="flex-1 px-3 py-2 rounded-lg text-sm focus:outline-none transition-all disabled:opacity-60"
                    style="background: var(--c-background); border: 1px solid var(--c-border); color: var(--c-text);"
                >
                <button
                    @click="sendMessage()"
                    :disabled="!conversationId || !newMessage.trim()"
                    class="p-2 rounded-lg transition-all hover:opacity-80 disabled:opacity-50"
                    style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\DATN\chill-drink\resources\views/components/chatbox.blade.php ENDPATH**/ ?>