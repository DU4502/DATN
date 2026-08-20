@extends('layouts.shipper')

@section('title', 'Chat chuyến giao')
@section('mobile-title', 'Chat')
@section('mobile-subtitle', 'Trao đổi ngắn với khách')

@section('content')
<div class="ship-chat-page">
    @if($selectedOrder)
        @php
            $selectedStatus = \App\Support\OrderStatus::normalize((string) $selectedOrder->status);
            $selectedLocked = in_array($selectedStatus, [\App\Support\OrderStatus::DELIVERED, \App\Support\OrderStatus::COMPLETED], true);
            $phone = preg_replace('/[^0-9+]/', '', (string) ($selectedOrder->customerPhone() ?? ''));
        @endphp
        <div class="ship-chat-detail-head">
            <a href="{{ route('shipper.chats.index') }}" class="ship-chat-back" aria-label="Quay lại danh sách chat"><i class="fa-solid fa-arrow-left"></i></a>
            <div class="ship-chat-person">
                <div class="ship-chat-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="min-w-0">
                    <b class="text-truncate">{{ $selectedOrder->customerName() ?: 'Khách hàng' }}</b>
                    <span class="text-truncate">{{ $selectedOrder->displayCode() }} · {{ \App\Support\OrderStatus::label($selectedStatus) }}</span>
                </div>
            </div>
            @if($phone)
                <a href="tel:{{ $phone }}" class="ship-chat-call" aria-label="Gọi khách"><i class="fa-solid fa-phone"></i></a>
            @endif
        </div>

        <div class="ship-chat-order-strip">
            <div><i class="fa-solid fa-location-dot"></i><span>{{ $selectedOrder->getShippingAddress() }}</span></div>
            <a href="{{ route('shipper.map', ['id' => $selectedOrder->id]) }}"><i class="fa-solid fa-location-arrow"></i> Dẫn đường</a>
        </div>

        @if(!$chatStorageReady)
            <div class="ship-empty"><i class="fa-solid fa-comments"></i><b>Chat đang được khởi tạo</b><p>Chưa có bảng dữ liệu chat. Hãy chạy migration của project trước.</p></div>
        @else
            <div class="ship-chat-shell"
                 data-shipper-chat-page
                 data-messages-url="{{ route('shipper.orders.delivery-chat.messages', $selectedOrder) }}"
                 data-send-url="{{ route('shipper.orders.delivery-chat.send', $selectedOrder) }}"
                 data-locked="{{ $selectedLocked ? '1' : '0' }}">
                <div class="ship-chat-state" data-chat-state>Đang tải cuộc trò chuyện...</div>
                <div class="ship-chat-messages" data-chat-messages></div>

                <div class="ship-chat-quick" data-chat-quick>
                    <button type="button" data-quick="Tôi sắp đến">Tôi sắp đến</button>
                    <button type="button" data-quick="Tôi đang ở trước cửa">Đang trước cửa</button>
                    <button type="button" data-quick="Bạn vui lòng xuống nhận hàng giúp mình nhé">Xuống nhận hàng</button>
                </div>

                <form class="ship-chat-form" data-chat-form>
                    @csrf
                    <input type="text" maxlength="500" autocomplete="off" placeholder="Nhập tin nhắn..." data-chat-input {{ $selectedLocked ? 'disabled' : '' }}>
                    <button type="submit" aria-label="Gửi tin nhắn" {{ $selectedLocked ? 'disabled' : '' }}><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>
        @endif
    @else
        <div class="ship-page-head">
            <div>
                <h1>Chat chuyến giao</h1>
                <p>Chỉ trao đổi thông tin ngắn với khách của những đơn được giao cho bạn. Tin nhắn tự hết hạn sau 24 giờ.</p>
            </div>
            <span class="ship-head-icon"><i class="fa-solid fa-comments"></i></span>
        </div>

        @if(!$chatStorageReady)
            <div class="ship-empty"><i class="fa-solid fa-comments"></i><b>Chat chưa sẵn sàng</b><p>Hãy chạy migration để tạo dữ liệu chat theo chuyến.</p></div>
        @elseif($orders->isEmpty())
            <div class="ship-empty"><i class="fa-solid fa-message"></i><b>Chưa có cuộc trò chuyện</b><p>Khi bạn nhận nhiệm vụ giao hàng, chat với khách sẽ xuất hiện ở đây.</p></div>
        @else
            <div class="ship-chat-list">
                @foreach($orders as $chatOrder)
                    @php
                        $last = $latestMessages->get($chatOrder->id);
                        $unread = (int) ($unreadByOrder->get($chatOrder->id) ?? 0);
                        $status = \App\Support\OrderStatus::normalize((string) $chatOrder->status);
                    @endphp
                    <a href="{{ route('shipper.chats.index', ['order' => $chatOrder->id]) }}" class="ship-chat-row {{ $unread > 0 ? 'is-unread' : '' }}">
                        <div class="ship-chat-avatar"><i class="fa-solid fa-user"></i></div>
                        <div class="ship-chat-row-main min-w-0">
                            <div class="ship-chat-row-top">
                                <b class="text-truncate">{{ $chatOrder->customerName() ?: 'Khách hàng' }}</b>
                                <span>{{ $last?->created_at?->format('H:i') ?: $chatOrder->updated_at?->format('H:i') }}</span>
                            </div>
                            <div class="ship-chat-code">{{ $chatOrder->displayCode() }} · {{ \App\Support\OrderStatus::label($status) }}</div>
                            <p class="text-truncate mb-0">{{ $last?->content ?: 'Chưa có tin nhắn. Nhấn để mở chat.' }}</p>
                        </div>
                        @if($unread > 0)<em>{{ $unread > 9 ? '9+' : $unread }}</em>@endif
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
.ship-chat-page{min-width:0}.ship-chat-detail-head{display:flex;align-items:center;gap:9px;margin:0 0 9px}.ship-chat-back,.ship-chat-call{width:40px;height:40px;border-radius:14px;border:1px solid var(--ship-line);background:#fff;color:var(--ship-ink);display:grid;place-items:center;text-decoration:none;flex:none}.ship-chat-call{color:var(--ship-green-dark)}
.ship-chat-person{display:flex;align-items:center;gap:8px;flex:1;min-width:0}.ship-chat-person b{display:block;font-size:13px}.ship-chat-person span{display:block;font-size:9.5px;color:var(--ship-muted);margin-top:2px}.ship-chat-avatar{width:38px;height:38px;border-radius:14px;background:#eaf2ff;color:#277cff;display:grid;place-items:center;flex:none}
.ship-chat-order-strip{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--ship-line);border-radius:16px;padding:9px 10px;margin-bottom:9px;font-size:9.5px}.ship-chat-order-strip>div{display:flex;gap:6px;align-items:flex-start;min-width:0;flex:1;color:#61716c}.ship-chat-order-strip>div span{overflow-wrap:anywhere}.ship-chat-order-strip>a{white-space:nowrap;text-decoration:none;font-weight:850;color:var(--ship-green-dark);font-size:9.5px}
.ship-chat-list{display:grid;gap:8px}.ship-chat-row{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--ship-line);border-radius:18px;padding:11px;text-decoration:none;color:var(--ship-ink);min-width:0;box-shadow:0 4px 12px rgba(16,55,44,.035)}.ship-chat-row.is-unread{border-color:#bfe4d5;background:#f7fffb}.ship-chat-row-main{flex:1}.ship-chat-row-top{display:flex;align-items:center;justify-content:space-between;gap:8px}.ship-chat-row-top b{font-size:12px;min-width:0}.ship-chat-row-top span{font-size:9px;color:var(--ship-muted);flex:none}.ship-chat-code{font-size:9px;color:var(--ship-green-dark);font-weight:800;margin-top:2px}.ship-chat-row p{font-size:10px;color:var(--ship-muted);margin-top:5px;max-width:100%}.ship-chat-row>em{font-style:normal;min-width:21px;height:21px;border-radius:999px;background:#ef4444;color:#fff;display:grid;place-items:center;padding:0 5px;font-size:9px;font-weight:850;flex:none}
.ship-chat-shell{height:calc(100dvh - 205px);min-height:430px;max-height:680px;background:#fff;border:1px solid var(--ship-line);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;box-shadow:var(--ship-shadow)}.ship-chat-state{padding:7px 10px;font-size:9.5px;color:var(--ship-muted);background:#f7faf9;border-bottom:1px solid var(--ship-line)}.ship-chat-messages{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;padding:12px;background:#f5f8f7;display:flex;flex-direction:column;gap:7px}.ship-chat-empty{margin:auto;text-align:center;color:#86958f;font-size:11px;max-width:240px}.ship-chat-msg{max-width:84%;display:flex;flex-direction:column;gap:2px}.ship-chat-msg.mine{align-self:flex-end;align-items:flex-end}.ship-chat-msg.other{align-self:flex-start;align-items:flex-start}.ship-chat-bubble{padding:8px 10px;border-radius:14px;font-size:11px;line-height:1.4;white-space:pre-wrap;overflow-wrap:anywhere}.ship-chat-msg.mine .ship-chat-bubble{background:var(--ship-green);color:#fff;border-bottom-right-radius:5px}.ship-chat-msg.other .ship-chat-bubble{background:#fff;border:1px solid #dde7e3;border-bottom-left-radius:5px}.ship-chat-meta{font-size:8.5px;color:#8a9893;padding:0 2px}
.ship-chat-quick{display:flex;flex-wrap:wrap;gap:5px;padding:8px;border-top:1px solid var(--ship-line);background:#fff}.ship-chat-quick button{flex:1 1 calc(50% - 5px);min-width:0;border:1px solid #cfe1dc;background:#f7fbfa;color:#0d7e65;border-radius:12px;padding:7px 6px;font-size:9px;font-weight:750;line-height:1.2;white-space:normal}.ship-chat-form{display:grid;grid-template-columns:minmax(0,1fr) 42px;gap:7px;padding:8px;border-top:1px solid var(--ship-line);background:#fff}.ship-chat-form input{width:100%;min-width:0;height:42px;border:1px solid #cfddd9;border-radius:14px;padding:0 11px;outline:none;font-size:11px}.ship-chat-form button{width:42px;height:42px;border:0;border-radius:14px;background:var(--ship-green);color:#fff}.ship-chat-form button:disabled,.ship-chat-form input:disabled{opacity:.55}
@media(max-width:360px){.ship-chat-quick button{flex-basis:100%}.ship-chat-shell{height:calc(100dvh - 190px)}}
</style>
@endpush

@if($selectedOrder && $chatStorageReady)
@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-shipper-chat-page]');
    if (!root) return;
    const messagesUrl = root.dataset.messagesUrl;
    const sendUrl = root.dataset.sendUrl;
    const stateEl = root.querySelector('[data-chat-state]');
    const messagesEl = root.querySelector('[data-chat-messages]');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const quick = root.querySelector('[data-chat-quick]');
    let sending = false;
    let firstLoad = true;

    const esc = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));

    function render(rows) {
        if (!rows?.length) {
            messagesEl.innerHTML = '<div class="ship-chat-empty"><i class="fa-regular fa-comments fs-4 d-block mb-2"></i>Chưa có tin nhắn. Bạn có thể gửi một câu ngắn để khách biết trạng thái giao hàng.</div>';
            return;
        }
        messagesEl.innerHTML = rows.map(row => `
            <div class="ship-chat-msg ${row.mine ? 'mine' : 'other'}">
                <div class="ship-chat-bubble">${esc(row.content)}</div>
                <div class="ship-chat-meta">${row.mine ? 'Bạn' : esc(row.sender_name)} · ${esc(row.time || '')}</div>
            </div>`).join('');
        if (firstLoad) messagesEl.scrollTop = messagesEl.scrollHeight;
        firstLoad = false;
    }

    async function load() {
        try {
            const res = await fetch(`${messagesUrl}?mark_read=1`, {headers:{'Accept':'application/json'}, cache:'no-store'});
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Không tải được chat.');
            stateEl.textContent = data.message || 'Chat theo chuyến';
            render(data.messages || []);
            const locked = !!data.locked || data.available === false;
            if (input) input.disabled = locked;
            const sendBtn = form?.querySelector('button[type="submit"]');
            if (sendBtn) sendBtn.disabled = locked;
            if (quick) quick.style.display = locked ? 'none' : 'flex';
        } catch (error) {
            stateEl.textContent = error.message || 'Không tải được chat.';
        }
    }

    async function send(text) {
        text = String(text || '').trim();
        if (!text || sending) return;
        sending = true;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(sendUrl, {method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({content:text})});
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Không gửi được tin nhắn.');
            if (input) input.value = '';
            firstLoad = true;
            await load();
        } catch (error) {
            stateEl.textContent = error.message || 'Không gửi được tin nhắn.';
        } finally { sending = false; }
    }

    form?.addEventListener('submit', event => { event.preventDefault(); send(input?.value); });
    quick?.addEventListener('click', event => { const btn = event.target.closest('[data-quick]'); if (btn) send(btn.dataset.quick); });
    load();
    setInterval(() => { if (!document.hidden) load(); }, 4000);
})();
</script>
@endpush
@endif
