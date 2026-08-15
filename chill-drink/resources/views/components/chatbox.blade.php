<style>
    [x-cloak] {
        display: none !important;
    }
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
        isCustomer: {{ auth()->check() && auth()->user()->isCustomer() ? 'true' : 'false' }},
        branchId: null,
        branchName: '',
        nearestBranches: [],
        loadingLocation: false,
        locationState: 'prompt',
        needLogin: false,
        selectingBranch: false,
        selectedBranchNameTemp: '',
        messages: [],
        newMessage: '',
        loading: false,
        pollInterval: null,
        unreadPollInterval: null,
        echoChannel: null,
        echoConnected: false,
        visibilityHandler: null,
        _activating: false,
        loadingConversation: true,
        loadingMessages: false,
        isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
        guestToken: null,
        guestName: '',
        showGuestModal: false,
        guestFormName: '',
        guestFormEmail: '',
        guestFormLoading: false,
        guestFormError: '',
        showEndSessionModal: false,
        endingSession: false,
        conversationStatus: 'open',
        showScrollToLatest: false,
        supportIssue: null,
        confirmChangeBranch: false,

        get hasUserSentMessage() {
            return this.messages.some(m => Number(m.sender_id) === Number(this.currentUserId));
        },

        async init() {
            this.groupChatAvailable = Boolean(document.querySelector('[data-vue-group-chat]'));
            window.__groupChatHostReady = true;
            window.dispatchEvent(new CustomEvent('group-chat-host-ready'));

            this.guestToken = localStorage.getItem('chat_guest_token') || null;
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

            if (!this.isCustomer && this.isLoggedIn) {
                return;
            }

            if (localStorage.getItem('support_chat_open') === 'true') {
                this.isOpen = true;
            }

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
                    this.showEndSessionModal = false;
                    this.showGuestModal = false;
                    this.stopPolling();
                    this.startUnreadPolling();
                }
            });

            if (this.isOpen) {
                this.activateSupportChat();
            } else {
                await this.fetchUnreadCount();
                this.subscribeEchoChannel();
                this.startUnreadPolling();
            }
            if (this.isLoggedIn) {
                await this.fetchUnreadCount();
                this.subscribeEchoChannel();
            }
            this.startUnreadPolling();
        },

        destroy() {
            if (this.isCustomer) {
                this.stopPolling();
                this.stopUnreadPolling();
                this.leaveEchoChannel();
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
        },

        async openSupportChat() {
            this.menuOpen = false;
            if (this.isOpen) {
                this.isOpen = false;
                localStorage.setItem('support_chat_open', 'false');
                return;
            }
            window.dispatchEvent(new CustomEvent('group-chat-close'));
            this.isOpen = true;
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
                if (this.isCustomer) {
                    this.menuOpen = !this.menuOpen;
                } else {
                    this.openGroupChat();
                }
                return;
            }
            if (this.isCustomer || !this.isLoggedIn) {
                await this.openSupportChat();
            }
        },

        async activateSupportChat() {
            if (document.hidden || !this.isOpen || this._activating) return;
            this._activating = true;

            if (document.hidden || !this.isOpen) return;

            if (!this.isLoggedIn && !this.guestToken) {
                this.showGuestModal = true;
                return;
            }

            if (!this.conversationId) {
                this.loadingConversation = true;
            }
            if (this.messages.length === 0) {
                this.loadingMessages = true;
            }

            try {
                if (!this.needLogin) {
                    await this.getOrCreateConversation();
                }
                if (!this.conversationId || !this.isOpen || this.needLogin) return;

                if (this.branchId) {
                    await this.fetchMessages(true);
                    this.subscribeEchoChannel();
                    this.startPolling();
                } else {
                    await this.requestLocationAndFetchBranches();
                }
            } finally {
                this.loadingConversation = false;
                this.loadingMessages = false;
                this._activating = false;
            }
            this.supportUnread = 0;
            if (this.isLoggedIn) this.subscribeEchoChannel();
            this.startPolling();
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
                let url = '{{ route('chat.nearest-branches', [], false) }}';
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
                const body = { conversation_id: this.conversationId, branch_id: branch.id };
                if (!this.isLoggedIn && this.guestToken) body.guest_token = this.guestToken;

                const res = await fetch('{{ route('chat.select-branch') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.success) {
                    if (data.conversation_id) {
                        this.conversationId = data.conversation_id;
                    }
                    this.branchId = data.branch_id;
                    this.branchName = data.branch_name;
                    if (data.message) {
                        this.messages = [data.message];
                    }
                    await this.fetchMessages(true);
                    if (this.isLoggedIn) this.subscribeEchoChannel();
                    this.startPolling();
                } else {
                    alert(data.message || 'Không thể chọn chi nhánh. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('Error selecting branch', e);
            } finally {
                this.selectingBranch = false;
                this.selectedBranchNameTemp = '';
            }
        },

        changeBranch() {
            if (this.messages.length > 0) {
                this.confirmChangeBranch = true;
                return;
            }
            this.proceedChangeBranch();
        },

        proceedChangeBranch() {
            this.confirmChangeBranch = false;
            this.branchId = null;
            this.branchName = '';
            this.messages = [];
            this.stopPolling();
            this.leaveEchoChannel();
            this.requestLocationAndFetchBranches();
        },

        async openEndSessionModal() {
            this.showEndSessionModal = true;
        },

        async confirmEndSession() {
            if (!this.conversationId) return;
            this.endingSession = true;
            try {
                const body = { conversation_id: this.conversationId };
                if (!this.isLoggedIn && this.guestToken) body.guest_token = this.guestToken;

                const res = await fetch('{{ route('chat.end-session') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.success) {
                    this.leaveEchoChannel();
                    this.showEndSessionModal = false;
                    this.branchId = null;
                    this.branchName = '';
                    this.conversationId = null;
                    this.messages = [];

                    if (!this.isLoggedIn) {
                        localStorage.removeItem('chat_guest_token');
                        this.guestToken = null;
                        this.guestName = '';
                        this.showGuestModal = true;
                    } else {
                        await this.getOrCreateConversation();
                        await this.requestLocationAndFetchBranches();
                    }
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
                .subscribed(() => {
                    this.echoConnected = true;
                    this.stopPolling();
                    this.stopUnreadPolling();
                })
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
                    this.echoConnected = false;
                    if (this.isOpen) this.startPolling();
                });
        },

        async fetchUnreadCount() {
            if (this.isOpen) {
                this.supportUnread = 0;
                return;
            }
            if (!this.isLoggedIn) return;
            if (!this.conversationId) {
                try {
                    const res = await fetch('{{ route('chat.index', [], false) }}');
                    const data = await res.json();
                    if (data.success) {
                        this.conversationId = data.conversation_id;
                        this.branchId       = data.branch_id;
                        this.branchName     = data.branch_name || '';
                        this.subscribeEchoChannel();
                    }
                } catch (e) { return; }
            }
            if (!this.conversationId) return;
            try {
                const res  = await fetch('{{ route('chat.messages', [], false) }}?conversation_id=' + this.conversationId);
                const data = await res.json();
                if (data.success) {
                    const uid = {{ auth()->id() ?? 0 }};
                    this.supportUnread = data.messages.filter(m => m.sender_id !== uid && !m.is_read && !m.is_guest_message).length;
                }
            } catch (e) { /* silent */ }
        },

        startUnreadPolling() {
            if (this.unreadPollInterval || this.isOpen || document.hidden || this.echoConnected) return;
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
            this.echoConnected = false;
        },

        startPolling() {
            this.stopPolling();
            if (!this.isOpen || document.hidden || !this.conversationId || !this.branchId || this.echoConnected) return;
            this.pollInterval = window.setInterval(() => {
                if (this.isOpen && !document.hidden) this.fetchMessages();
            }, 3000);
        },

        stopPolling() {
            if (!this.pollInterval) return;
            window.clearInterval(this.pollInterval);
            this.pollInterval = null;
        },

        async getOrCreateConversation() {
            try {
                let url = '{{ route('chat.index') }}';
                if (!this.isLoggedIn && this.guestToken) {
                    url += '?guest_token=' + encodeURIComponent(this.guestToken);
                }
                const res = await fetch(url);
                if (res.status === 401 || res.redirected) {
                    this.needLogin = true;
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    this.needLogin = false;
                    this.conversationId = data.conversation_id;
                    this.branchId       = data.branch_id;
                    this.branchName     = data.branch_name || '';
                    this.conversationStatus = data.status || 'open';
                    this.supportIssue = data.support_issue || null;
                    if (data.guest_token) {
                        this.guestToken = data.guest_token;
                        localStorage.setItem('chat_guest_token', data.guest_token);
                    }
                    if (data.guest_name) this.guestName = data.guest_name;
                } else if (data.requires_guest_init) {
                    localStorage.removeItem('chat_guest_token');
                    this.guestToken = null;
                    this.showGuestModal = true;
                }
            } catch (e) {
                console.error('Error getting conversation', e);
            }
        },

        async fetchMessages(markRead = false) {
            if (!this.conversationId) return;
            if (this.messages.length === 0) {
                this.loadingMessages = true;
            }
            try {
                let url = '{{ route('chat.messages') }}?conversation_id=' + this.conversationId
                    + (markRead ? '&mark_as_read=1' : '');
                if (!this.isLoggedIn && this.guestToken) {
                    url += '&guest_token=' + encodeURIComponent(this.guestToken);
                }
                const res = await fetch(url);
                const data = await res.json();
                if (data.success && Array.isArray(data.messages)) {
                    this.messages = data.messages;
                    this.supportIssue = data.support_issue || this.supportIssue;
                    this.$nextTick(() => {
                        this.scrollToBottom(markRead);
                    });
                    if (markRead) this.supportUnread = 0;
                }
            } catch (e) {
                console.error('Error fetching messages', e);
            } finally {
                this.loadingMessages = false;
            }
        },

        isNearMessageBottom() {
            const el = this.$refs.messageList;
            return !el || (el.scrollHeight - el.scrollTop - el.clientHeight) < 72;
        },

        handleMessageScroll() {
            this.showScrollToLatest = !this.isNearMessageBottom();
        },

        scrollToBottom(force = false) {
            const el = this.$refs.messageList;
            if (!el) return;
            if (!force && !this.isNearMessageBottom()) {
                this.showScrollToLatest = true;
                return;
            }
            el.scrollTop = el.scrollHeight;
            this.showScrollToLatest = false;
        },

        scrollToLatest() {
            this.scrollToBottom(true);
        },

        isOrderSupportMessage(message) {
            return /^\[(CẬP NHẬT|YÊU CẦU) HỖ TRỢ ĐƠN/.test(message?.content || '');
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.conversationId) return;

            const text = this.newMessage.trim();
            this.newMessage = '';

            const tempId = 'tmp_' + Date.now();
            const displayName = this.isLoggedIn
                ? '{{ addslashes(auth()->user()->name ?? 'Khách hàng') }}'
                : (this.guestName || 'Khách vãng lai');
            const tempMsg = {
                id: tempId,
                conversation_id: this.conversationId,
                sender_id: this.isLoggedIn ? {{ auth()->id() ?? 0 }} : null,
                is_guest_message: !this.isLoggedIn,
                guest_sender_name: !this.isLoggedIn ? displayName : null,
                content: text,
                attachment_path: null,
                attachment_name: null,
                is_read: false,
                created_at: new Date().toISOString(),
                sender: { id: this.isLoggedIn ? {{ auth()->id() ?? 0 }} : null, name: displayName },
                _pending: true,
            };
            this.messages.push(tempMsg);
            this.$nextTick(() => { this.scrollToBottom(true); });

            this.loading = true;
            const formData = new FormData();
            formData.append('conversation_id', this.conversationId);
            formData.append('content', text);
            if (!this.isLoggedIn && this.guestToken) {
                formData.append('guest_token', this.guestToken);
            }

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
                    this.messages = this.messages.filter(m => m.id !== tempId);
                    this.messages.push(data.message);
                    this.$nextTick(() => {
                        this.scrollToBottom(true);
                    });
                } else {
                    console.error('Server error:', data.message);
                    this.messages = this.messages.filter(m => m.id !== tempId);
                    this.newMessage = text;
                    alert(data.message || 'Không thể gửi tin nhắn. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('Error sending message', e);
                this.messages = this.messages.filter(m => m.id !== tempId);
                this.newMessage = text;
                alert('Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.');
            } finally {
                this.loading = false;
            }
        },

        async submitGuestForm() {
            if (!this.guestFormName.trim() || !this.guestFormEmail.trim()) {
                this.guestFormError = 'Vui lòng nhập đủ Tên và Email.';
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.guestFormEmail.trim())) {
                this.guestFormError = 'Email không hợp lệ.';
                return;
            }
            this.guestFormError = '';
            this.guestFormLoading = true;
            try {
                const res = await fetch('{{ route('chat.guest-init') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        guest_name: this.guestFormName.trim(),
                        guest_email: this.guestFormEmail.trim(),
                        guest_token: this.guestToken,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.conversationId = data.conversation_id;
                    this.branchId = data.branch_id;
                    this.branchName = data.branch_name || '';
                    this.guestToken = data.guest_token;
                    this.guestName = data.guest_name;
                    localStorage.setItem('chat_guest_token', data.guest_token);
                    this.showGuestModal = false;
                    if (!this.branchId) {
                        await this.requestLocationAndFetchBranches();
                    } else {
                        await this.fetchMessages(true);
                    }
                    this.startPolling();
                } else {
                    this.guestFormError = data.message || 'Có lỗi xảy ra. Vui lòng thử lại.';
                }
            } catch (e) {
                this.guestFormError = 'Lỗi kết nối. Vui lòng kiểm tra mạng.';
            } finally {
                this.guestFormLoading = false;
            }
        },
    }"
    x-show="!isLoggedIn || isCustomer || groupChatAvailable"
    class="fixed bottom-6 right-6 z-50" style="position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1050;">
    <!-- Floating Toggle Button (Always visible at bottom right, z-index 1060 above modal window) -->
    <button
        type="button"
        @click.prevent.stop="toggleUnifiedChat()"
        class="flex items-center justify-center rounded-full shadow-2xl transition-all duration-300 hover:scale-110"
        style="position: relative; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; outline: none; z-index: 1060;">
        <svg x-show="!isOpen && !groupChatOpen" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg x-show="isOpen || groupChatOpen" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        {{-- Badge group unread (chỉ hiện khi không có support unread và chatbox đóng) --}}
        <span x-show="groupUnread > 0 && supportUnread === 0 && !isOpen && !groupChatOpen" x-text="groupUnread > 99 ? '99+' : groupUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        {{-- Badge support unread (hỗ trợ khách hàng - chỉ hiện khi chatbox đang đóng) --}}
        <span x-show="supportUnread > 0 && !isOpen" x-text="supportUnread > 99 ? '99+' : supportUnread" style="position:absolute;top:-4px;right:-4px;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;"></span>
        {{-- Badge tổng cộng khi có cả 2 và chatbox đang đóng --}}
        <template x-if="groupUnread > 0 && supportUnread > 0 && !isOpen && !groupChatOpen">
            <span style="position:absolute;bottom:-4px;left:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#5b50d6;color:#fff;border:2px solid #fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;" x-text="groupUnread > 9 ? '9+' : groupUnread"></span>
        </template>
    </button>

    <!-- Unified Menu Popup -->
    <div
        x-show="menuOpen && !groupChatOpen"
        x-cloak
        @click.away="menuOpen = false"
        class="rounded-2xl shadow-2xl p-2 border space-y-1 transition-all duration-200"
        style="position: fixed; right: 1.5rem; bottom: 6.25rem; width: min(320px, calc(100vw - 2rem)); background: #ffffff; border-color: var(--c-border); z-index: 1061;">
        <button
            type="button"
            @click.prevent="openSupportChat()"
            class="w-full text-left px-3 py-2.5 rounded-xl transition-all flex items-center justify-between group"
            style="background: transparent;"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='transparent'">
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
            onmouseout="this.style.background='transparent'">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: #ec4899; color: white;">
                    👥
                </div>
                <div>
                    <div class="text-sm font-bold flex items-center gap-1.5" style="color: var(--c-text);">
                        <span>Chat đơn nhóm</span>
                        <span x-show="groupUnread > 0" class="w-2 h-2 rounded-full" style="background: #dc3545;"></span>
                    </div>
                    <div class="text-xs opacity-70" style="color: var(--c-text);">Trò chuyện với thành viên trong phòng</div>
                </div>
            </div>
            <span x-show="groupUnread > 0" x-text="groupUnread" class="px-2 py-0.5 rounded-full text-xs font-bold text-white" style="background: #dc3545;"></span>
        </button>
    </div>

    <!-- Main Customer Support Chat Window Popup -->
    <div
        x-show="isOpen && !groupChatOpen"
        x-cloak
        class="rounded-2xl shadow-2xl flex flex-col overflow-hidden border transition-all duration-300 relative"
        style="position: fixed; right: 1.5rem; bottom: 6.25rem; width: min(380px, calc(100vw - 2rem)); height: min(540px, calc(100vh - 8rem)); background: #ffffff; border-color: var(--c-border); z-index: 1055;">

        <!-- Custom Confirm Change Branch Overlay -->
        <template x-if="confirmChangeBranch">
            <div class="absolute inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(255,255,255,0.85); backdrop-filter: blur(4px);">
                <div class="bg-white rounded-2xl shadow-xl border p-4 text-center w-full max-w-[280px]" style="border-color: #e2e8f0;">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl shadow-sm" style="background: #fef2f2; color: #ef4444;">
                        ⚠️
                    </div>
                    <h3 class="font-bold text-sm text-slate-800 mb-2">Đổi chi nhánh?</h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        Lịch sử trò chuyện hiện tại sẽ được đóng lại để bắt đầu phiên mới. Bạn có chắc chắn muốn đổi?
                    </p>
                    <div class="flex gap-2 w-full">
                        <button type="button" @click.prevent="confirmChangeBranch = false" class="flex-1 py-2 rounded-xl text-xs font-bold text-slate-700 transition-colors" style="background: #f1f5f9;">
                            Hủy
                        </button>
                        <button type="button" @click.prevent="proceedChangeBranch()" class="flex-1 py-2 rounded-xl text-xs font-bold text-white transition-colors shadow-sm" style="background: #ef4444;">
                            Đổi ngay
                        </button>
                    </div>
                </div>
            </div>
        </template>

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
                        <template x-if="branchId && !loadingMessages && !loadingConversation">
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

        <div
            x-ref="messageList"
            @scroll.passive="handleMessageScroll()"
            class="flex-1 p-3 overflow-y-auto space-y-3"
            style="min-height: 0; background: #ffffff !important;">

            <!-- Case: Unauthenticated User Prompt -->
            <template x-if="needLogin">
                <div class="flex flex-col items-center justify-center h-full gap-3 py-8 text-center px-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl" style="background: #fef3c7; color: #d97706;">
                        🔐
                    </div>
                    <div class="font-bold text-sm text-slate-800">Vui lòng đăng nhập</div>
                    <p class="text-xs text-slate-500 mb-2">Đăng nhập tài khoản để kết nối trực tiếp với nhân viên hỗ trợ chi nhánh.</p>
                    <a
                        href="{{ route('login', [], false) }}"
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-md active:scale-95"
                        style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); text-decoration: none;">
                        Đăng nhập ngay
                    </a>
                </div>
            </template>

            <!-- Loading state when fetching conversation or initial messages -->
            <template x-if="(loadingConversation || (branchId && loadingMessages && messages.length === 0)) && !needLogin">
                <div class="flex flex-col items-center justify-center h-full gap-3 py-8">
                    <div class="spinner-border spinner-border-sm text-emerald-600" role="status" style="width: 28px; height: 28px;"></div>
                    <p class="text-xs text-slate-500 mb-0">Đang tải tin nhắn...</p>
                </div>
            </template>

            <!-- STEP 1 & 2: Sequential Location State Machine & Branch Selection Cards -->
            <template x-if="!loadingConversation && conversationId && !branchId && !needLogin">
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
                                style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent));">
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
                                                x-text="branch.distance_text"></span>
                                        </div>
                                        <div class="text-xs text-slate-500 mb-2 leading-tight" x-text="branch.address"></div>

                                        <button
                                            type="button"
                                            @click.prevent="selectBranch(branch)"
                                            :disabled="selectingBranch"
                                            class="w-full py-2 rounded-xl text-xs font-bold text-white flex items-center justify-center gap-1.5 transition-all shadow-sm active:scale-95"
                                            style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent));">
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

            <!-- Danh sách tin nhắn luôn được gắn vào DOM để cập nhật ngay sau khi gửi. -->
            <div class="space-y-3">
                <template x-for="message in messages" :key="message.id">
                    <div
                        :class="[
                            'flex w-full mb-2',
                            (isLoggedIn ? (message.sender_id == currentUserId) : message.is_guest_message) ? 'justify-end' : 'justify-start'
                        ]">
                        <div
                            :class="[
                                'max-w-[85%] rounded-2xl px-3.5 py-2.5 shadow-sm text-sm break-words',
                                (isLoggedIn ? (message.sender_id == currentUserId) : message.is_guest_message) ? 'rounded-tr-none' : 'rounded-tl-none'
                            ]"
                                :style="(isLoggedIn ? (message.sender_id == currentUserId) : message.is_guest_message) ? 'background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;' : (message.content && message.content.includes('🤖 Hệ thống') ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0;')">
                                <div x-text="message.content" x-show="message.content" class="mb-1" style="white-space: pre-line;"></div>
                                <template x-if="isOrderSupportMessage(message) && supportIssue">
                                    <a :href="supportIssue.url" class="mt-2 flex items-center gap-2 rounded-xl p-2 text-decoration-none" style="background: #ffffff; border: 1px solid #cbe9df; color: #153d34;">
                                        <img x-show="supportIssue.image_url" :src="supportIssue.image_url" :alt="supportIssue.product_name" class="h-12 w-12 flex-shrink-0 rounded-lg object-cover" style="background: #edf8f4;">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-bold" x-text="supportIssue.product_name"></span>
                                            <span class="block text-[10px]" style="color: #6b7c76;"><span x-text="supportIssue.type"></span> · <span x-text="supportIssue.status_label"></span></span>
                                            <span class="mt-1 inline-block text-[10px] font-bold" style="color: #008b70;">Xem chi tiết <i class="bi bi-arrow-right"></i></span>
                                        </span>
                                    </a>
                                </template>
                                <div
                                x-text="message.created_at ? new Date(message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false }) : ''"
                                class="text-[11px] opacity-70"
                                :title="message.created_at ? new Date(message.created_at).toLocaleString('vi-VN') : ''"></div>
                        </div>
                    </div>
                </template>
            </div>
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
                        style="background: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a;">
                    <button
                        type="button"
                        @click.prevent="sendMessage()"
                        :disabled="loading || !newMessage.trim()"
                        class="p-2 rounded-xl transition-all hover:opacity-90 disabled:opacity-50"
                        style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <button
            type="button"
            x-show="showScrollToLatest"
            x-cloak
            @click="scrollToLatest()"
            aria-label="Cuộn xuống tin nhắn mới nhất"
            title="Tin nhắn mới nhất"
            class="rounded-full shadow-lg transition-transform hover:scale-105"
            style="position: absolute; left: 50%; bottom: 4.75rem; transform: translateX(-50%); width: 42px; height: 42px; border: 0; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: #fff; z-index: 10;">
            <i class="bi bi-arrow-down" style="font-size: 1.25rem;"></i>
        </button>

        <!-- Guest Info Modal — Hiện khi khách vãng lai mở chatbox lần đầu -->
        <div
            x-show="showGuestModal && !isLoggedIn"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1.25rem; border-radius: 1.25rem;">
            <div style="background: #ffffff; border-radius: 1rem; padding: 1.5rem 1.25rem; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
                <!-- Icon & Title -->
                <div style="text-align: center; margin-bottom: 1rem;">
                    <div style="width: 3.5rem; height: 3.5rem; border-radius: 50%; background: #edf9f5; color: #00a870; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem auto; font-size: 1.7rem;">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 700; color: #111827;">Bắt đầu chat với chúng tôi</h4>
                    <p style="margin: 0; font-size: 0.78rem; color: #6b7280; line-height: 1.4;">Vui lòng cho chúng tôi biết thông tin của bạn để hỗ trợ tốt hơn.</p>
                </div>

                <!-- Form -->
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Tên của bạn <span style="color: #dc2626;">*</span></label>
                        <input
                            type="text"
                            x-model="guestFormName"
                            @keydown.enter.prevent="submitGuestForm()"
                            placeholder="Ví dụ: Nguyễn Văn A"
                            :disabled="guestFormLoading"
                            style="width: 100%; padding: 0.55rem 0.8rem; border-radius: 0.65rem; border: 1.5px solid #d1d5db; font-size: 0.82rem; outline: none; background: #f9fafb; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Email <span style="color: #dc2626;">*</span></label>
                        <input
                            type="email"
                            x-model="guestFormEmail"
                            @keydown.enter.prevent="submitGuestForm()"
                            placeholder="email@example.com"
                            :disabled="guestFormLoading"
                            style="width: 100%; padding: 0.55rem 0.8rem; border-radius: 0.65rem; border: 1.5px solid #d1d5db; font-size: 0.82rem; outline: none; background: #f9fafb; box-sizing: border-box;">
                    </div>

                    <!-- Error message -->
                    <div x-show="guestFormError" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 0.5rem 0.7rem;">
                        <p style="margin: 0; font-size: 0.75rem; color: #dc2626;" x-text="guestFormError"></p>
                    </div>

                    <!-- Submit button -->
                    <button
                        @click="submitGuestForm()"
                        :disabled="guestFormLoading"
                        type="button"
                        style="width: 100%; background: #00a870; color: white; border: none; border-radius: 0.65rem; padding: 0.65rem; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: background 0.2s; margin-top: 0.25rem;">
                        <span x-show="!guestFormLoading">Bắt đầu chat ngay</span>
                        <span x-show="guestFormLoading" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <span class="spinner-border spinner-border-sm" role="status"></span> Đang xử lý...
                        </span>
                    </button>

                    <!-- Link đăng nhập -->
                    <p style="margin: 0.25rem 0 0 0; text-align: center; font-size: 0.75rem; color: #6b7280;">
                        Đã có tài khoản?
                        <a href="{{ route('login') }}" style="color: #00a870; font-weight: 600; text-decoration: none;">Đăng nhập</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
