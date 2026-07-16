<div x-data="{
        isOpen: false,
        conversationId: null,
        messages: [],
        newMessage: '',
        loading: false,
        pollInterval: null,
        tick: 0,

        get unreadCount() {
            return this.messages.filter(m => m.sender_id != {{ auth()->id() ?? 0 }} && !m.is_read).length;
        },

        async init() {
            await this.getOrCreateConversation();
            if (this.conversationId) {
                await this.fetchMessages();
                
                this.$watch('isOpen', async (newVal) => {
                    if (newVal) {
                        await this.fetchMessages();
                    }
                });

                // Poll: 1s if open, 3s if closed
                this.pollInterval = setInterval(() => {
                    if (this.conversationId) {
                        this.tick++;
                        if (this.isOpen || this.tick % 3 === 0) {
                            this.fetchMessages();
                        }
                    }
                }, 1000);
            }
        },

        async getOrCreateConversation() {
            try {
                const res = await fetch('{{ route('chat.index') }}');
                const data = await res.json();
                if (data.success) {
                    this.conversationId = data.conversation_id;
                }
            } catch (e) {
                console.error('Error getting conversation', e);
            }
        },

        async fetchMessages() {
            try {
                const markAsRead = this.isOpen ? '&mark_as_read=1' : '';
                const res = await fetch('{{ route('chat.messages') }}?conversation_id=' + this.conversationId + markAsRead);
                const data = await res.json();
                if (data.success) {
                    // Update only if counts or unread state changes to avoid unnecessary layout updates
                    const hasUpdates = data.messages.length !== this.messages.length || 
                        JSON.stringify(data.messages.map(m => m.is_read)) !== JSON.stringify(this.messages.map(m => m.is_read));
                    
                    if (hasUpdates) {
                        this.messages = data.messages;
                        if (this.isOpen) {
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        }
                    }
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
            if (!this.newMessage) return;

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
    }" class="fixed bottom-6 right-6 z-50" style="position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1050;">
    <!-- Toggle button -->
    <button @click="isOpen = !isOpen"
        class="flex items-center justify-center w-16 h-16 rounded-full shadow-xl transition-all duration-300 hover:scale-110"
        style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); position: relative;">
        <!-- Unread badge -->
        <template x-if="unreadCount > 0">
            <span x-text="unreadCount" 
                  class="bg-danger text-white rounded-full text-xs font-bold shadow-md"
                  style="position: absolute; top: -2px; right: -2px; min-width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; z-index: 100;">
            </span>
        </template>
        <svg x-show="!isOpen" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg x-show="isOpen" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Chat window -->
    <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="absolute bottom-20 right-0 w-80 max-w-[85vw] rounded-2xl shadow-2xl overflow-hidden flex flex-col"
        style="position: absolute; right: 0; bottom: 5rem; width: 20rem; max-width: calc(100vw - 2rem); height: min(450px, calc(100vh - 7rem)); max-height: calc(100vh - 7rem); display: flex; flex-direction: column; overflow: hidden; background: var(--c-surface);">
        <!-- Header -->
        <div class="p-3 border-b flex-shrink-0"
            style="flex: 0 0 auto; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); border-color: var(--c-border);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-sm">Hỗ trợ khách hàng</h3>
                    </div>
                </div>
            </div>
        </div>



        <!-- Messages -->
        <div x-ref="messageList" class="flex-1 p-3 overflow-y-auto space-y-3"
            style="min-height: 0; background: var(--c-background);">
            <template x-for="message in messages" :key="message.id">
                <div :class="[
                        'flex w-full',
                        message.sender_id == {{ auth()->id() }} ? 'justify-end' : 'justify-start'
                    ]">
                    <div :class="[
                            'max-w-[85%] rounded-xl px-3 py-2 shadow-sm text-sm break-words',
                            message.sender_id == {{ auth()->id() }} ? 'rounded-tr-none' : 'rounded-tl-none'
                        ]"
                        :style="message.sender_id == {{ auth()->id() }} ? 'background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;' : 'background: var(--c-surface); color: var(--c-text); border: 1px solid var(--c-border);'">
                        <div x-text="message.content" x-show="message.content" class="mb-1"></div>
                        <div x-text="new Date(message.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })"
                            class="text-xs opacity-70" :title="new Date(message.created_at).toLocaleString('vi-VN')">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input -->
        <div class="p-3 border-t flex-shrink-0"
            style="flex: 0 0 auto; background: var(--c-surface); border-color: var(--c-border);">
            <div class="flex items-center gap-2">
                <input type="text" x-model="newMessage" @keydown.enter.prevent="sendMessage()"
                    placeholder="Nhập tin nhắn..."
                    class="flex-1 px-3 py-2 rounded-lg text-sm focus:outline-none transition-all"
                    style="background: var(--c-background); border: 1px solid var(--c-border); color: var(--c-text);">
                <button @click="sendMessage()" :disabled="loading"
                    class="p-2 rounded-lg transition-all hover:opacity-80 disabled:opacity-50"
                    style="background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>