import { createApp } from 'vue';

const formatRemaining = (closesAt) => {
    const seconds = Math.max(0, Math.ceil((closesAt - Date.now()) / 1000));
    return {
        seconds,
        text: `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`,
    };
};

const groupOrderIndex = {
    props: {
        rootElement: { type: Object, required: true },
    },
    mounted() {
        this.root = this.rootElement;
        this.timers = [];
        this.root.querySelectorAll('[data-group-countdown]').forEach((countdown) => {
            const closesAt = new Date(countdown.dataset.closesAt).getTime();
            const time = countdown.querySelector('[data-countdown-time]');
            const card = countdown.closest('[data-group-card]');
            let finished = false;

            const tick = () => {
                const remaining = formatRemaining(closesAt);
                time.textContent = remaining.text;
                countdown.classList.toggle('is-urgent', remaining.seconds > 0 && remaining.seconds <= 60);

                if (remaining.seconds === 0 && !finished) {
                    finished = true;
                    countdown.classList.remove('is-urgent');
                    countdown.classList.add('is-finished');
                    countdown.querySelector('span:not([data-countdown-time])').textContent = 'Đã hết giờ';
                    card?.classList.add('has-just-expired');
                    const status = card?.querySelector('[data-group-status]');
                    status?.classList.remove('is-open');
                    status?.classList.add('is-closed');
                    const statusText = card?.querySelector('[data-group-status-text]');
                    if (statusText) statusText.textContent = 'Đã đóng';
                    window.setTimeout(() => window.location.reload(), 1500);
                }
            };

            tick();
            this.timers.push(window.setInterval(tick, 1000));
        });
    },
    beforeUnmount() {
        this.timers?.forEach(window.clearInterval);
    },
};

const groupOrderRoom = {
    props: {
        rootElement: { type: Object, required: true },
    },
    mounted() {
        this.root = this.rootElement;
        this.timers = [];
        this.abortController = new AbortController();
        this.isRefreshing = false;
        const signal = this.abortController.signal;

        this.root.addEventListener('submit', (event) => this.submitAsync(event), { signal });

        const copyButton = this.root.querySelector('[data-copy-group-link]');
        copyButton?.addEventListener('click', () => this.copyLink(copyButton), { signal });

        this.setupProductPicker(signal);
        this.setupCountdowns();
        this.setupPresence(signal);
        this.setupLiveRoom(signal);
    },
    beforeUnmount() {
        this.abortController?.abort();
        this.timers?.forEach(window.clearInterval);
    },
    methods: {
        showMessage(message, isError = false) {
            document.querySelector('.group-live-toast')?.remove();
            const toast = document.createElement('div');
            toast.className = `group-live-toast${isError ? ' is-error' : ''}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            window.setTimeout(() => toast.remove(), 2600);
        },
        async submitAsync(event) {
            const form = event.target.closest('form[data-group-async-action]');
            if (!form || event.defaultPrevented) return;
            event.preventDefault();

            const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
            if (submitter) submitter.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const error = page.querySelector('.alert-danger');
                if (!response.ok || error) {
                    this.showMessage(error?.textContent.trim() || 'Không thể cập nhật món. Vui lòng thử lại.', true);
                    return;
                }

                const isJoining = form.hasAttribute('data-group-join');
                this.replaceLiveSections(page, isJoining);
                if (isJoining) {
                    this.setupProductPicker(this.abortController.signal);
                    mountGroupChats(this.root);
                }
                this.showMessage(page.querySelector('.alert-success')?.textContent.trim() || 'Đã cập nhật đơn nhóm.');
            } catch {
                this.showMessage('Kết nối bị gián đoạn. Vui lòng thử lại.', true);
            } finally {
                if (submitter?.isConnected) submitter.disabled = false;
            }
        },
        replaceLiveSections(page, includeParticipation = false) {
            const selectors = ['[data-group-order-heading]', '[data-group-members]', '[data-group-summary]'];
            if (includeParticipation) selectors.unshift('[data-group-participation]');
            selectors.forEach((selector) => {
                const current = this.root.querySelector(selector);
                const updated = page.querySelector(selector);
                if (current && updated && current.outerHTML !== updated.outerHTML) current.replaceWith(updated);
            });
        },
        setupLiveRoom(signal) {
            const refresh = async () => {
                if (document.hidden || this.isRefreshing) return;
                this.isRefreshing = true;
                try {
                    const response = await fetch(window.location.href, {
                        headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal,
                    });
                    if (!response.ok) return;
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    this.replaceLiveSections(page, false);
                } catch (error) {
                    if (error.name !== 'AbortError') return;
                } finally {
                    this.isRefreshing = false;
                }
            };

            this.timers.push(window.setInterval(refresh, 3000));
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refresh();
            }, { signal });
        },
        async copyLink(button) {
            const input = this.root.querySelector('#groupShareUrl');
            try {
                await navigator.clipboard.writeText(input.value);
            } catch {
                input.select();
                document.execCommand('copy');
            }
            button.innerHTML = '<i class="bi bi-check2 me-1"></i>Đã sao chép';
            window.setTimeout(() => { button.innerHTML = '<i class="bi bi-copy me-1"></i>Sao chép'; }, 1800);
        },
        setupProductPicker(signal) {
            const picker = this.root.querySelector('[data-product-picker]');
            const search = this.root.querySelector('[data-product-search]');
            const value = this.root.querySelector('[data-product-value]');
            if (!picker || !search || !value) return;

            const options = Array.from(picker.querySelectorAll('[data-product-option]'));
            const empty = picker.querySelector('[data-product-empty]');
            const labels = Array.from(this.root.querySelectorAll('[data-topping-id]'));
            const noToppings = this.root.querySelector('[data-no-toppings]');
            const chooseProduct = this.root.querySelector('[data-choose-product-for-toppings]');
            const help = this.root.querySelector('[data-topping-help]');
            const normalize = (text) => text.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
            const open = () => picker.classList.add('is-open');
            const showToppings = (option) => {
                const ids = new Set((option?.dataset.toppings || '').split(',').filter(Boolean).map(Number));
                chooseProduct?.classList.toggle('d-none', Boolean(option));
                if (help) help.textContent = option ? 'Chỉ hiển thị topping dùng được với món đã chọn.' : 'Hãy chọn đồ uống trước để xem topping phù hợp.';
                labels.forEach((label) => {
                    const allowed = ids.has(Number(label.dataset.toppingId));
                    label.classList.toggle('d-none', !allowed);
                    if (!allowed) label.querySelector('input').checked = false;
                });
                noToppings?.classList.toggle('d-none', !option || ids.size > 0);
            };

            search.addEventListener('focus', open, { signal });
            search.addEventListener('click', open, { signal });
            search.addEventListener('input', () => {
                const keyword = normalize(search.value.trim());
                value.value = '';
                showToppings(null);
                search.setCustomValidity('Vui lòng chọn một đồ uống trong danh sách.');
                const matches = options.filter((option) => normalize(option.dataset.search).includes(keyword));
                options.forEach((option) => { option.hidden = !matches.includes(option); });
                empty?.classList.toggle('is-visible', matches.length === 0);
                open();
            }, { signal });
            options.forEach((option) => option.addEventListener('click', () => {
                value.value = option.dataset.value;
                search.value = option.dataset.name;
                search.setCustomValidity('');
                search.classList.remove('is-invalid');
                picker.classList.remove('is-open');
                showToppings(option);
            }, { signal }));
            document.addEventListener('click', (event) => {
                if (!picker.contains(event.target)) picker.classList.remove('is-open');
            }, { signal });
            search.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') picker.classList.remove('is-open');
            }, { signal });
            search.closest('form')?.addEventListener('submit', (event) => {
                if (value.value) return;
                event.preventDefault();
                search.classList.add('is-invalid');
                search.setCustomValidity('Vui lòng tìm và chọn một đồ uống trong danh sách.');
                search.reportValidity();
                open();
            }, { signal });
        },
        setupCountdowns() {
            const countdown = this.root.querySelector('[data-group-countdown]');
            if (countdown) {
                const closesAt = new Date(countdown.dataset.closesAt).getTime();
                const tick = () => {
                    const remaining = formatRemaining(closesAt);
                    countdown.textContent = remaining.text;
                    if (remaining.seconds === 0) window.location.reload();
                };
                tick();
                this.timers.push(window.setInterval(tick, 1000));
            }

            const away = this.root.querySelector('[data-owner-away-notice]');
            const awayCountdown = this.root.querySelector('[data-owner-away-countdown]');
            if (away && awayCountdown) {
                const closesAt = new Date(away.dataset.closesAt).getTime();
                const tick = () => { awayCountdown.textContent = formatRemaining(closesAt).text; };
                tick();
                this.timers.push(window.setInterval(tick, 1000));
            }
        },
        setupPresence(signal) {
            const away = this.root.querySelector('[data-owner-away-notice]');
            const presenceUrl = away?.dataset.presenceUrl || this.root.dataset.presenceUrl;
            const leaveUrl = this.root.dataset.leaveUrl;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!presenceUrl || !csrf) return;

            let reloading = false;
            let previousOwnerPresence = null;
            const sync = async () => {
                if (document.hidden) return;
                try {
                    const response = await fetch(presenceUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        signal,
                    });
                    if (!response.ok) return;
                    const state = await response.json();
                    away?.classList.toggle('is-visible', state.is_open && !state.owner_present);
                    if (previousOwnerPresence === true && state.is_open && !state.owner_present) {
                        this.showMessage('Chủ nhóm đã rời khỏi phòng. Bạn vẫn có thể tiếp tục chọn món.', true);
                    }
                    if (previousOwnerPresence === false && state.owner_present) {
                        this.showMessage('Chủ nhóm đã quay lại phòng.');
                    }
                    previousOwnerPresence = state.owner_present;
                    if (!state.is_open && !reloading) {
                        reloading = true;
                        window.location.reload();
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') return;
                }
            };
            sync();
            this.timers.push(window.setInterval(sync, 3000));
            const reportOwnerLeft = () => {
                if (!leaveUrl) return;
                const data = new FormData();
                data.append('_token', csrf);
                navigator.sendBeacon(leaveUrl, data);
            };
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) reportOwnerLeft();
                else sync();
            }, { signal });
            window.addEventListener('pagehide', reportOwnerLeft, { signal });
        },
    },
};

const groupOrderChat = {
    props: {
        messagesUrl: { type: String, required: true },
        sendUrl: { type: String, required: true },
        readUrl: { type: String, required: true },
        groupId: { type: Number, required: true },
        memberId: { type: Number, required: true },
        initialMembers: { type: Array, required: true },
    },
    data() {
        return { messages: [], members: this.initialMembers, recipientId: null, content: '', attachment: null, loading: false, syncing: false, sending: false, error: '', timer: null, isOpen: false, notificationsReady: false, notifications: [], seenPrivateMessageIds: {}, lastIncomingPrivateId: 0, lastIncomingGroupId: 0, lastMarkedReadId: 0, privateUnread: 0, groupUnread: 0, unreadCounts: {}, showContacts: false, memberSearch: '' };
    },
    computed: {
        currentMemberId() { return this.memberId; },
        otherMembers() { return this.members.filter((member) => Number(member.id) !== this.currentMemberId); },
        filteredMembers() { const keyword = this.memberSearch.trim().toLocaleLowerCase('vi'); return this.otherMembers.filter((member) => member.name.toLocaleLowerCase('vi').includes(keyword)); },
        totalPrivateUnread() { return Object.values(this.unreadCounts).reduce((total, count) => total + Number(count || 0), 0); },
        totalUnread() { return this.totalPrivateUnread + this.groupUnread; },
        title() {
            if (!this.recipientId) return 'Chat chung';
            return `Chat riêng với ${this.members.find((member) => Number(member.id) === Number(this.recipientId))?.name || 'thành viên'}`;
        },
    },
    mounted() {
        this.loadMessages(true);
        this.timer = window.setInterval(() => this.loadMessages(false), 800);
    },
    beforeUnmount() { window.clearInterval(this.timer); },
    methods: {
        selectRecipient(id) {
            this.recipientId = id;
            this.messages = [];
            if (!id) this.groupUnread = 0;
            if (id) this.privateUnread = 0;
            if (id) { this.showContacts = false; this.unreadCounts[id] = 0; }
            this.loadMessages(true);
        },
        async loadMessages(showLoading) {
            if (this.syncing || (document.hidden && !showLoading)) return;
            this.syncing = true;
            const requestedRecipientId = this.recipientId ? Number(this.recipientId) : null;
            if (showLoading) this.loading = true;
            let conversationChangedWhileLoading = false;
            try {
                const query = requestedRecipientId ? `?recipient_id=${requestedRecipientId}` : '';
                const response = await fetch(this.messagesUrl + query, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) return;
                const data = await response.json();
                const activeRecipientId = this.recipientId ? Number(this.recipientId) : null;
                if (activeRecipientId !== requestedRecipientId) {
                    conversationChangedWhileLoading = true;
                    return;
                }
                if (Number(data.group_id) !== this.groupId) {
                    this.messages = [];
                    this.recipientId = null;
                    this.lastIncomingPrivateId = 0;
                    return;
                }
                const nextMembers = data.members || [];
                const membersChanged = nextMembers.length !== this.members.length || nextMembers.some((member, index) => Number(member.id) !== Number(this.members[index]?.id) || member.name !== this.members[index]?.name);
                if (membersChanged) this.members = nextMembers;
                this.unreadCounts = data.private_unread_counts || {};
                const incoming = data.latest_incoming_private;
                const latestGroup = data.latest_group_message;
                const recentGroupMessages = data.recent_group_messages || [];
                const privateBySender = data.latest_unread_private_by_sender || [];
                if (!this.notificationsReady) {
                    this.lastIncomingPrivateId = Number(incoming?.id || 0);
                    this.lastIncomingGroupId = Math.max(0, ...recentGroupMessages.map((message) => Number(message.id)));
                    privateBySender.forEach((message) => { this.seenPrivateMessageIds[message.sender_id] = Number(message.id); });
                    this.notificationsReady = true;
                } else {
                    privateBySender.forEach((message) => {
                        const previousId = Number(this.seenPrivateMessageIds[message.sender_id] || 0);
                        if (Number(message.id) > previousId) {
                            this.seenPrivateMessageIds[message.sender_id] = Number(message.id);
                            this.notifyIncoming(message, 'Tin nhắn riêng', true);
                        }
                    });
                    const newGroupMessages = recentGroupMessages.filter((message) => Number(message.id) > this.lastIncomingGroupId);
                    if (newGroupMessages.length) this.lastIncomingGroupId = Math.max(...newGroupMessages.map((message) => Number(message.id)));
                    newGroupMessages.filter((message) => Number(message.sender_id) !== this.currentMemberId).forEach((message) => {
                        if (this.recipientId || !this.isOpen) this.groupUnread += 1;
                        this.notifyIncoming(message, 'Tin nhắn nhóm', false);
                    });
                }
                if (incoming && Number(incoming.id) > this.lastIncomingPrivateId) {
                    this.lastIncomingPrivateId = Number(incoming.id);
                    if (Number(requestedRecipientId) !== Number(incoming.sender_id)) {
                        this.privateUnread += 1;
                    }
                }
                const nextMessages = data.messages || [];
                const currentLast = this.messages[this.messages.length - 1];
                const nextLast = nextMessages[nextMessages.length - 1];
                const currentReadState = this.messages.map((message) => `${message.id}:${message.read_at || ''}`).join('|');
                const nextReadState = nextMessages.map((message) => `${message.id}:${message.read_at || ''}`).join('|');
                const changed = this.messages.length !== nextMessages.length || currentLast?.id !== nextLast?.id || currentReadState !== nextReadState;
                if (changed) {
                    const hasNewMessage = nextLast?.id !== currentLast?.id;
                    this.messages = nextMessages;
                    if (hasNewMessage) this.$nextTick(() => { const box = this.$refs.messages; if (box) box.scrollTop = box.scrollHeight; });
                }
                const newestUnread = [...nextMessages].reverse().find((message) => Number(message.sender_id) === requestedRecipientId && !message.read_at);
                if (requestedRecipientId && this.isOpen && newestUnread && Number(newestUnread.id) > this.lastMarkedReadId) {
                    this.lastMarkedReadId = Number(newestUnread.id);
                    this.markAsRead(requestedRecipientId);
                }
            } finally {
                this.loading = false;
                this.syncing = false;
                const activeRecipientId = this.recipientId ? Number(this.recipientId) : null;
                if (conversationChangedWhileLoading || activeRecipientId !== requestedRecipientId) window.setTimeout(() => this.loadMessages(false), 0);
            }
        },
        async markAsRead(senderId) {
            await fetch(this.readUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ sender_id: senderId }),
            }).catch(() => {});
        },
        notifyIncoming(message, type, isPrivate) {
            const preview = message.content || message.attachment_name || 'Đã gửi một tệp';
            const text = `${type} từ ${message.sender_name}: ${preview}`;
            const notification = { id: `${isPrivate ? 'p' : 'g'}-${message.id}`, text, senderId: Number(message.sender_id), isPrivate };
            if (!this.notifications.some((item) => item.id === notification.id)) this.notifications.push(notification);
            window.setTimeout(() => this.dismissNotification(notification.id), 6000);
        },
        dismissNotification(id) { this.notifications = this.notifications.filter((item) => item.id !== id); },
        openNotification(notification) {
            this.isOpen = true;
            this.selectRecipient(notification.isPrivate ? notification.senderId : null);
            this.dismissNotification(notification.id);
        },
        async send() {
            const content = this.content.trim();
            if ((!content && !this.attachment) || this.sending) return;
            this.sending = true;
            this.error = '';
            try {
                const form = new FormData();
                form.append('content', content);
                if (this.recipientId) form.append('recipient_id', this.recipientId);
                if (this.attachment) form.append('attachment', this.attachment);
                const response = await fetch(this.sendUrl, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: form });
                const data = await response.json();
                if (!response.ok) { this.error = data.message || 'Không thể gửi tin nhắn.'; return; }
                this.messages.push(data.message);
                this.content = '';
                this.attachment = null;
                if (this.$refs.imageFile) this.$refs.imageFile.value = '';
                if (this.$refs.documentFile) this.$refs.documentFile.value = '';
                this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; });
            } catch { this.error = 'Kết nối bị gián đoạn.'; }
            finally { this.sending = false; }
        },
        chooseFile(event) {
            const file = event.target.files?.[0] || null;
            if (file && file.size > 10 * 1024 * 1024) { this.error = 'Tệp không được vượt quá 10 MB.'; event.target.value = ''; return; }
            this.attachment = file;
            this.error = '';
        },
        isImage(message) { return Boolean(message.attachment_mime?.startsWith('image/')); },
        fileSize(bytes) { return bytes ? `${(bytes / 1024 / 1024).toFixed(bytes > 1048576 ? 1 : 2)} MB` : ''; },
        time(value) { return new Date(value).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }); },
    },
    template: `
        <div>
        <div class="group-chat-notification-stack">
            <button v-for="notification in notifications" :key="notification.id" type="button" class="group-chat-notification" @click="openNotification(notification)">
                <i class="bi" :class="notification.isPrivate ? 'bi-person-lock' : 'bi-people'"></i><span>{{ notification.text }}</span><i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <button type="button" class="group-chat-launcher" @click="isOpen = !isOpen" :aria-label="isOpen ? 'Đóng chat đơn nhóm' : 'Mở chat đơn nhóm'" title="Chat đơn nhóm">
            <i class="bi" :class="isOpen ? 'bi-x-lg' : 'bi-people-fill'"></i>
            <span v-if="totalUnread" class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">{{ totalUnread }}</span>
        </button>
        <section v-show="isOpen" class="group-card group-chat-panel">
            <header class="group-chat-head">
                <div><div class="group-eyebrow">Trò chuyện trong phòng</div><strong>{{ title }}</strong></div>
                <button type="button" class="btn btn-sm btn-light rounded-circle" @click="isOpen = false" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
            </header>
            <div class="group-chat-tools">
                    <button type="button" class="group-chat-tab" :class="{ 'is-active': !recipientId }" @click="selectRecipient(null)"><i class="bi bi-people me-1"></i>Cả nhóm</button>
                    <button type="button" class="group-chat-private-button" :class="{ 'is-active': recipientId }" @click="showContacts = !showContacts"><i class="bi bi-person-lock me-1"></i>Chat riêng <span v-if="totalPrivateUnread" class="badge bg-danger ms-1">{{ totalPrivateUnread }}</span><i class="bi bi-chevron-down ms-auto"></i></button>
            </div>
            <div v-show="showContacts" class="group-chat-contacts">
                <div class="group-chat-contact-search"><i class="bi bi-search"></i><input v-model="memberSearch" placeholder="Tìm thành viên..."></div>
                <div class="group-chat-contact-list">
                    <button v-for="member in filteredMembers" :key="member.id" type="button" class="group-chat-contact" :class="{ 'is-active': Number(recipientId) === Number(member.id) }" @click="selectRecipient(Number(member.id))"><span class="member-avatar">{{ member.name.charAt(0).toUpperCase() }}</span><strong>{{ member.name }}</strong><span v-if="Number(unreadCounts[member.id] || 0)" class="badge rounded-pill bg-danger">{{ unreadCounts[member.id] }}</span></button>
                    <div v-if="!filteredMembers.length" class="text-center text-secondary small py-3">Không tìm thấy thành viên.</div>
                </div>
            </div>
            <div class="group-chat-messages" ref="messages">
                <div v-if="loading && !messages.length" class="text-center text-secondary">Đang tải tin nhắn...</div>
                <div v-else-if="!messages.length" class="text-center text-secondary">Chưa có tin nhắn. Hãy bắt đầu trò chuyện!</div>
                <div v-for="message in messages" :key="message.id" class="group-chat-message" :class="{ 'is-mine': Number(message.sender_id) === currentMemberId }">
                    <div class="group-chat-bubble"><small class="d-block fw-bold mb-1">{{ message.sender_name }} · {{ time(message.created_at) }}</small><span v-if="message.content">{{ message.content }}</span><a v-if="message.attachment_url && isImage(message)" :href="message.attachment_url" target="_blank" class="d-block mt-2"><img :src="message.attachment_url" :alt="message.attachment_name" class="group-chat-image"></a><a v-else-if="message.attachment_url" :href="message.attachment_url" target="_blank" class="group-chat-file"><i class="bi bi-file-earmark-arrow-down"></i><span><strong>{{ message.attachment_name }}</strong><small>{{ fileSize(message.attachment_size) }}</small></span></a><small v-if="recipientId && Number(message.sender_id) === currentMemberId && message.read_at" class="group-chat-read"><i class="bi bi-check2-all"></i> Đã xem</small></div>
                </div>
            </div>
            <form class="group-chat-compose" @submit.prevent="send">
                <input ref="imageFile" type="file" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif" @change="chooseFile">
                <input ref="documentFile" type="file" class="d-none" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" @change="chooseFile">
                <button type="button" class="group-chat-attach is-image" @click="$refs.imageFile.click()" title="Gửi ảnh"><i class="bi bi-image"></i></button>
                <button type="button" class="group-chat-attach is-file" @click="$refs.documentFile.click()" title="Gửi tệp"><i class="bi bi-file-earmark-plus"></i></button>
                <div class="flex-grow-1"><input :value="content" @input="content = $event.target.value" maxlength="1000" class="form-control group-input" :placeholder="recipientId ? 'Nhắn riêng...' : 'Nhắn cho cả nhóm...'"><small v-if="attachment" class="group-chat-selected-file"><i class="bi" :class="attachment.type.startsWith('image/') ? 'bi-image' : 'bi-file-earmark'"></i>{{ attachment.name }} <button type="button" @click="attachment = null; $refs.imageFile.value = ''; $refs.documentFile.value = ''">×</button></small></div>
                <button class="btn btn-primary group-chat-send" :disabled="sending || (!content.trim() && !attachment)" aria-label="Gửi tin nhắn"><i class="bi bi-send"></i></button>
            </form>
            <div v-if="error" class="text-danger small px-3 pb-3">{{ error }}</div>
        </section>
        </div>`,
};

const mountGroupChats = (scope = document) => {
    scope.querySelectorAll('[data-vue-group-chat]:not([data-v-app])').forEach((root) => createApp(groupOrderChat, {
        messagesUrl: root.dataset.messagesUrl,
        sendUrl: root.dataset.sendUrl,
        readUrl: root.dataset.readUrl,
        groupId: Number(root.dataset.groupId),
        memberId: Number(root.dataset.currentMemberId),
        initialMembers: JSON.parse(root.dataset.members || '[]'),
    }).mount(root));
};

document.addEventListener('DOMContentLoaded', () => {
    const indexRoot = document.querySelector('[data-vue-group-orders-index]');
    if (indexRoot) {
        const mountPoint = document.createElement('span');
        mountPoint.hidden = true;
        indexRoot.appendChild(mountPoint);
        createApp(groupOrderIndex, { rootElement: indexRoot }).mount(mountPoint);
    }

    const roomRoot = document.querySelector('[data-vue-group-order-room]');
    if (roomRoot) {
        const mountPoint = document.createElement('span');
        mountPoint.hidden = true;
        roomRoot.appendChild(mountPoint);
        createApp(groupOrderRoom, { rootElement: roomRoot }).mount(mountPoint);
    }

    mountGroupChats();
});
