@props([
    'order',
    'messagesUrl',
    'sendUrl',
    'viewer' => 'customer',
    'peerLabel' => 'Tài xế',
    'buttonText' => 'Chat',
    'buttonClass' => 'btn btn-outline-primary',
])

@php
    $rootId = 'deliveryOrderChat_' . $viewer . '_' . $order->id;
    $quickReplies = $viewer === 'shipper'
        ? ['Tôi sắp đến', 'Tôi đang ở trước cửa', 'Vui lòng xuống nhận hàng', 'Tôi không tìm thấy địa chỉ']
        : ['Mình đang xuống nhận hàng', 'Mình ở cổng sau', 'Bạn chờ mình một chút nhé', 'Mình sẽ nghe máy ngay'];
@endphp

<span class="order-delivery-chat-entry" data-order-chat-entry>
    <button type="button" class="{{ $buttonClass }}" data-order-chat-open>
        <i class="bi bi-chat-dots me-1"></i>{{ $buttonText }}
        <span class="order-delivery-chat-badge d-none" data-order-chat-badge>0</span>
    </button>
</span>

<div id="{{ $rootId }}"
     class="order-delivery-chat-overlay"
     data-order-delivery-chat
     data-messages-url="{{ $messagesUrl }}"
     data-send-url="{{ $sendUrl }}"
     data-viewer="{{ $viewer }}"
     data-peer-label="{{ $peerLabel }}"
     aria-hidden="true">
    <div class="order-delivery-chat-panel" role="dialog" aria-modal="true" aria-label="Chat theo đơn hàng">
        <div class="order-delivery-chat-head">
            <div>
                <div class="order-delivery-chat-title"><i class="bi bi-chat-heart me-2"></i>{{ $peerLabel }}</div>
                <div class="order-delivery-chat-sub">Chat theo chuyến · tự hết hạn sau 24 giờ</div>
            </div>
            <button type="button" class="order-delivery-chat-close" data-order-chat-close aria-label="Đóng">×</button>
        </div>

        <div class="order-delivery-chat-state" data-order-chat-state>Đang tải cuộc trò chuyện...</div>
        <div class="order-delivery-chat-messages" data-order-chat-messages></div>

        <div class="order-delivery-chat-quick" data-order-chat-quick>
            @foreach($quickReplies as $quick)
                <button type="button" data-order-chat-quick-value="{{ $quick }}">{{ $quick }}</button>
            @endforeach
        </div>

        <form class="order-delivery-chat-form" data-order-chat-form>
            <input type="hidden" name="_token" value="{{ csrf_token() }}" data-order-chat-token>
            <input type="text" maxlength="500" autocomplete="off" placeholder="Nhập tin nhắn..." data-order-chat-input>
            <button type="submit" data-order-chat-send aria-label="Gửi"><i class="bi bi-send-fill"></i></button>
        </form>
    </div>
</div>

@once
<style>
.order-delivery-chat-entry{display:block}
.order-delivery-chat-overlay{position:fixed;inset:0;background:rgba(15,23,42,.38);display:none;align-items:flex-end;justify-content:center;padding:12px;z-index:2500}
.order-delivery-chat-overlay.is-open{display:flex}
.order-delivery-chat-panel{width:min(390px,calc(100vw - 24px));height:min(590px,calc(100dvh - 40px));background:#fff;border-radius:22px;box-shadow:0 24px 70px rgba(15,23,42,.28);display:flex;flex-direction:column;overflow:hidden;border:1px solid #dce9e5}
/* Shipper dùng một app mobile duy nhất kể cả trên PC: chat phải nằm bên trong đúng khung app 480px, không bay ra ngoài viewport desktop. */
.order-delivery-chat-overlay[data-viewer="shipper"]{
    inset:64px auto 0 50%;
    width:min(100vw,480px);
    transform:translateX(-50%);
    padding:8px;
    align-items:flex-end;
    justify-content:center;
    background:rgba(15,23,42,.42);
}
.order-delivery-chat-overlay[data-viewer="shipper"] .order-delivery-chat-panel{
    width:100%;
    height:min(76dvh,650px);
    max-height:calc(100dvh - 76px);
    border-radius:24px 24px 0 0;
    box-shadow:0 -12px 36px rgba(15,23,42,.22);
}
body.order-delivery-chat-open{overflow:hidden}
.order-delivery-chat-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 16px;background:linear-gradient(135deg,#0d9373,#08765e);color:#fff}
.order-delivery-chat-title{font-weight:900;font-size:1rem}.order-delivery-chat-sub{font-size:.72rem;opacity:.86;margin-top:2px}
.order-delivery-chat-close{width:34px;height:34px;border:0;border-radius:50%;background:rgba(255,255,255,.16);color:#fff;font-size:1.45rem;line-height:1;cursor:pointer}
.order-delivery-chat-state{padding:8px 14px;background:#f7fbfa;border-bottom:1px solid #e7efec;color:#60716c;font-size:.76rem}
.order-delivery-chat-messages{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px;background:#f6f9f8;display:flex;flex-direction:column;gap:8px}
.order-delivery-chat-empty{margin:auto;text-align:center;color:#84938f;font-size:.86rem;max-width:240px}
.order-delivery-chat-msg{max-width:82%;display:flex;flex-direction:column;gap:3px}.order-delivery-chat-msg.mine{align-self:flex-end;align-items:flex-end}.order-delivery-chat-msg.other{align-self:flex-start;align-items:flex-start}
.order-delivery-chat-bubble{padding:9px 11px;border-radius:15px;font-size:.9rem;line-height:1.42;white-space:pre-wrap;word-break:break-word}.mine .order-delivery-chat-bubble{background:#0d9373;color:#fff;border-bottom-right-radius:5px}.other .order-delivery-chat-bubble{background:#fff;color:#26332f;border:1px solid #dfe9e6;border-bottom-left-radius:5px}
.order-delivery-chat-meta{font-size:.66rem;color:#84938f;padding:0 3px}
.order-delivery-chat-quick{display:flex;flex-wrap:wrap;gap:6px;overflow:visible;padding:9px 11px;border-top:1px solid #e6eeeb;background:#fff}.order-delivery-chat-quick button{flex:1 1 calc(50% - 6px);min-width:0;border:1px solid #cfe1dc;background:#f7fbfa;color:#0d7e65;border-radius:12px;padding:7px 8px;font-size:.72rem;font-weight:700;line-height:1.2;white-space:normal;cursor:pointer}
.order-delivery-chat-form{display:flex;gap:8px;padding:10px;border-top:1px solid #e5eeeb;background:#fff}.order-delivery-chat-form input{flex:1;border:1px solid #cfddd9;border-radius:999px;padding:9px 13px;outline:none;font-size:.9rem}.order-delivery-chat-form input:focus{border-color:#0d9373;box-shadow:0 0 0 3px rgba(13,147,115,.1)}.order-delivery-chat-form button{width:40px;height:40px;border-radius:50%;border:0;background:#0d9373;color:#fff;display:grid;place-items:center}.order-delivery-chat-form.is-locked{display:none}.order-delivery-chat-quick.is-locked{display:none}
.order-delivery-chat-entry button{position:relative}
.order-delivery-chat-badge{position:absolute;top:-8px;right:-8px;min-width:20px;height:20px;border-radius:999px;background:#dc2626;color:#fff;font-size:.68rem;font-weight:800;display:grid;place-items:center;padding:0 6px;box-shadow:0 8px 18px rgba(220,38,38,.28)}
.order-delivery-chat-badge.is-pulse{animation:orderChatBadgePulse 1.2s ease-in-out infinite}
.order-delivery-chat-toast-stack{position:fixed;left:50%;right:auto;bottom:84px;width:min(100vw,480px);transform:translateX(-50%);padding:0 12px;display:flex;flex-direction:column;align-items:stretch;gap:10px;z-index:3100;pointer-events:none}
.order-delivery-chat-toast{width:100%;min-width:0;max-width:none;background:#103a31;color:#fff;border-radius:16px;padding:12px 14px;box-shadow:0 20px 45px rgba(15,23,42,.22);border:1px solid rgba(255,255,255,.12);opacity:0;transform:translateY(10px);transition:opacity .2s ease,transform .2s ease}
.order-delivery-chat-toast.is-visible{opacity:1;transform:translateY(0)}
.order-delivery-chat-toast-title{font-size:.78rem;font-weight:900;letter-spacing:.02em;text-transform:uppercase;color:#9fe7d2}
.order-delivery-chat-toast-body{font-size:.92rem;font-weight:700;line-height:1.45;margin-top:3px}
@keyframes orderChatBadgePulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}
@media(max-width:600px){
.order-delivery-chat-overlay{padding:0;align-items:flex-end}
.order-delivery-chat-panel{width:100%;height:min(76dvh,650px);border-radius:22px 22px 0 0}
.order-delivery-chat-overlay[data-viewer="shipper"]{left:0;right:0;width:100%;transform:none;padding:0;inset:64px 0 0}
.order-delivery-chat-overlay[data-viewer="shipper"] .order-delivery-chat-panel{height:min(76dvh,650px);max-height:calc(100dvh - 64px)}
.order-delivery-chat-toast-stack{left:0;right:0;width:100%;transform:none;padding:0 10px}
}
</style>

<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const titleState = window.__orderDeliveryChatTitleState || {
        base: document.title,
        unread: 0,
        apply() {
            document.title = this.unread > 0 ? `(${this.unread}) Tin nhan moi - ${this.base}` : this.base;
        }
    };
    window.__orderDeliveryChatTitleState = titleState;

    function toastStack() {
        let stack = document.querySelector('[data-order-chat-toast-stack]');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'order-delivery-chat-toast-stack';
            stack.setAttribute('data-order-chat-toast-stack', '');
            document.body.appendChild(stack);
        }

        return stack;
    }

    function showIncomingToast(title, body) {
        const stack = toastStack();
        const toast = document.createElement('div');
        toast.className = 'order-delivery-chat-toast';
        toast.innerHTML = `
            <div class="order-delivery-chat-toast-title">${escapeHtml(title)}</div>
            <div class="order-delivery-chat-toast-body">${escapeHtml(body)}</div>
        `;
        stack.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 3600);
    }

    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));

    document.querySelectorAll('[data-order-delivery-chat]').forEach(root => {
        if (root.dataset.booted === '1') return;
        root.dataset.booted = '1';

        const entry = root.previousElementSibling;
        const openBtn = entry?.querySelector('[data-order-chat-open]');
        const closeBtn = root.querySelector('[data-order-chat-close]');
        const messagesEl = root.querySelector('[data-order-chat-messages]');
        const stateEl = root.querySelector('[data-order-chat-state]');
        const form = root.querySelector('[data-order-chat-form]');
        const tokenInput = root.querySelector('[data-order-chat-token]');
        const input = root.querySelector('[data-order-chat-input]');
        const quick = root.querySelector('[data-order-chat-quick]');
        const badge = entry?.querySelector('[data-order-chat-badge]');
        const viewer = root.dataset.viewer;
        const peerLabel = root.dataset.peerLabel || 'Tin nhan';
        let pollTimer = null;
        let busy = false;
        let bootstrapped = false;
        let highestSeenId = 0;
        let unreadCount = 0;

        function setUnread(count) {
            unreadCount = Math.max(0, Number(count) || 0);
            if (badge) {
                badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                badge.classList.toggle('d-none', unreadCount <= 0);
                badge.classList.toggle('is-pulse', unreadCount > 0);
            }
            titleState.unread = Array.from(document.querySelectorAll('[data-order-chat-badge]'))
                .reduce((sum, el) => sum + (el.classList.contains('d-none') ? 0 : (parseInt(el.textContent, 10) || 0)), 0);
            titleState.apply();
        }

        function analyzeIncoming(rows, notify) {
            if (!Array.isArray(rows) || rows.length === 0) {
                bootstrapped = true;
                return;
            }

            const newestId = rows.reduce((max, row) => Math.max(max, Number(row.id) || 0), highestSeenId);
            const incoming = rows.filter(row => !row.mine && (Number(row.id) || 0) > highestSeenId);

            if (bootstrapped && notify && incoming.length > 0) {
                if (!root.classList.contains('is-open')) {
                    setUnread(unreadCount + incoming.length);
                }

                if (!root.classList.contains('is-open') || document.hidden) {
                    const latest = incoming[incoming.length - 1];
                    showIncomingToast(peerLabel, latest.content || 'Ban co tin nhan moi');
                }
            }

            highestSeenId = newestId;
            bootstrapped = true;
        }

        function startPolling() {
            if (pollTimer) return;
            pollTimer = window.setInterval(() => {
                loadMessages({
                    scrollBottom: false,
                    markRead: root.classList.contains('is-open'),
                    render: root.classList.contains('is-open'),
                    notify: true,
                });
            }, 4000);
        }

        const syncBodyLock = () => {
            document.body.classList.toggle('order-delivery-chat-open', !!document.querySelector('[data-order-delivery-chat].is-open'));
        };
        const open = () => {
            root.classList.add('is-open');
            root.setAttribute('aria-hidden','false');
            syncBodyLock();
            setUnread(0);
            loadMessages({scrollBottom:true, markRead:true, render:true, notify:false});
            setTimeout(() => input?.focus(), 80);
        };
        const close = () => {
            root.classList.remove('is-open');
            root.setAttribute('aria-hidden','true');
            syncBodyLock();
        };

        const renderMessages = rows => {
            if (!Array.isArray(rows) || rows.length === 0) {
                messagesEl.innerHTML = '<div class="order-delivery-chat-empty"><i class="bi bi-chat-dots fs-3 d-block mb-2"></i>Chưa có tin nhắn. Chỉ dùng chat này cho thông tin ngắn trong chuyến giao.</div>';
                return;
            }
            messagesEl.innerHTML = rows.map(row => `
                <div class="order-delivery-chat-msg ${row.mine ? 'mine' : 'other'}">
                    <div class="order-delivery-chat-bubble">${escapeHtml(row.content)}</div>
                    <div class="order-delivery-chat-meta">${row.mine ? 'Bạn' : escapeHtml(row.sender_name)} · ${escapeHtml(row.time || '')}</div>
                </div>
            `).join('');
        };

        async function loadMessages(options = {}) {
            if (busy) return;
            busy = true;
            const {
                scrollBottom = false,
                markRead = true,
                render = true,
                notify = false,
            } = options;
            try {
                const url = new URL(root.dataset.messagesUrl, window.location.origin);
                url.searchParams.set('mark_read', markRead ? '1' : '0');

                const res = await fetch(url, {
                    headers:{
                        'Accept':'application/json',
                        'X-Requested-With':'XMLHttpRequest'
                    },
                    cache:'no-store',
                    credentials:'same-origin'
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Không tải được chat');
                stateEl.textContent = data.message || 'Chat theo chuyến';
                analyzeIncoming(data.messages || [], notify);
                if (render) {
                    renderMessages(data.messages || []);
                }
                form.classList.toggle('is-locked', !!data.locked || !data.available);
                quick.classList.toggle('is-locked', !!data.locked || !data.available);
                if (render && (scrollBottom || messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80)) {
                    requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
                }
                if (markRead && root.classList.contains('is-open')) {
                    setUnread(0);
                }
            } catch (e) {
                stateEl.textContent = e.message || 'Không tải được chat';
            } finally {
                busy = false;
            }
        }

        async function send(content) {
            const text = String(content || '').trim();
            if (!text) return;
            try {
                const token = tokenInput?.value || csrf;
                const payload = new URLSearchParams();
                payload.set('_token', token);
                payload.set('content', text);

                const res = await fetch(root.dataset.sendUrl, {
                    method:'POST',
                    headers:{
                        'Accept':'application/json',
                        'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN':token,
                        'X-Requested-With':'XMLHttpRequest'
                    },
                    credentials:'same-origin',
                    body: payload.toString(),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Không gửi được tin nhắn');
                input.value = '';
                await loadMessages({scrollBottom:true, markRead:true, render:true, notify:false});
            } catch (e) {
                stateEl.textContent = e.message || 'Không gửi được tin nhắn';
            }
        }

        openBtn?.addEventListener('click', open);
        closeBtn?.addEventListener('click', close);
        root.addEventListener('click', e => { if (e.target === root) close(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && root.classList.contains('is-open')) close(); });
        form?.addEventListener('submit', e => { e.preventDefault(); send(input.value); });
        quick?.querySelectorAll('[data-order-chat-quick-value]').forEach(btn => btn.addEventListener('click', () => send(btn.dataset.orderChatQuickValue)));
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && root.classList.contains('is-open')) {
                loadMessages({scrollBottom:false, markRead:true, render:true, notify:false});
            }
        });

        loadMessages({scrollBottom:false, markRead:false, render:false, notify:false});
        startPolling();
    });
})();
</script>
@endonce
