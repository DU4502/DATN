@if(\App\Support\RealtimeOrderNotifier::isConfigured() && auth()->user()?->isStaffOnly() && is_numeric(auth()->user()?->branch_id))
<div id="staffNewOrderAlert" class="staff-order-alert" hidden>
    <div class="staff-order-alert__backdrop"></div>
    <section class="staff-order-alert__dialog" role="dialog" aria-modal="true" aria-labelledby="staffNewOrderAlertTitle">
        <header class="staff-order-alert__header">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="staff-order-alert__tag"><i class="bi bi-bell-fill"></i>Cảnh báo đơn mới</div>
                    <h2 id="staffNewOrderAlertTitle" class="staff-order-alert__code" data-alert-code></h2>
                    <div class="staff-order-alert__muted" data-alert-created></div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" data-alert-disable><i class="bi bi-bell-slash me-1"></i>Tắt cảnh báo</button>
            </div>
            <div class="alert alert-warning mt-3 mb-0 d-none" data-alert-block></div>
        </header>
        <div class="staff-order-alert__body">
            <div class="staff-order-alert__grid">
                <section class="staff-order-alert__card"><div class="staff-order-alert__label">Khách hàng</div><div class="staff-order-alert__value" data-alert-customer></div><div class="staff-order-alert__muted mt-1" data-alert-contact></div></section>
                <section class="staff-order-alert__card"><div class="staff-order-alert__label">Thanh toán</div><div class="staff-order-alert__value" data-alert-payment></div><div class="staff-order-alert__muted mt-1" data-alert-payment-status></div><div class="fw-bold text-primary mt-2" data-alert-total></div></section>
            </div>
            <section class="staff-order-alert__card mt-3"><div class="staff-order-alert__label">Địa chỉ giao hàng</div><div class="staff-order-alert__value" data-alert-address></div></section>
            <section class="staff-order-alert__items mt-3">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-2"><div><div class="staff-order-alert__label">Chi tiết món</div><div class="staff-order-alert__muted" data-alert-item-count></div></div><a href="#" class="btn btn-outline-secondary btn-sm fw-semibold" data-alert-open>Mở trang đơn</a></div>
                <ul class="list-unstyled mb-0" data-alert-items></ul>
            </section>
            <section class="staff-order-alert__note mt-3 d-none" data-alert-note-wrap><div class="staff-order-alert__label">Ghi chú khách</div><div class="staff-order-alert__value" data-alert-note></div></section>
            <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary fw-semibold" data-alert-snooze><i class="bi bi-clock-history me-1"></i>Xem sau 5 phút</button>
                <button type="button" class="btn btn-success fw-semibold" data-alert-confirm><i class="bi bi-check2-circle me-1"></i>Nhận / Xác nhận đơn</button>
            </div>
        </div>
    </section>
</div>
<button id="staffNewOrderAlertEnable" type="button" class="staff-order-alert__enable" hidden><i class="bi bi-bell-fill"></i><span>Bật cảnh báo đơn mới</span></button>

<style>
.staff-order-alert{position:fixed;inset:0;z-index:11000;display:grid;place-items:center;padding:18px}.staff-order-alert[hidden]{display:none}.staff-order-alert__backdrop{position:absolute;inset:0;background:rgba(9,19,17,.58);backdrop-filter:blur(3px)}
.staff-order-alert__dialog{position:relative;width:min(760px,100%);max-height:min(88vh,920px);overflow:auto;border-radius:22px;background:#fff;box-shadow:0 28px 80px rgba(0,0,0,.25)}.staff-order-alert__header{padding:22px 24px 18px;background:linear-gradient(135deg,#fff9e8,#fff);border-bottom:1px solid #eee5cd}.staff-order-alert__body{padding:22px 24px 24px}
.staff-order-alert__tag{display:flex;align-items:center;gap:7px;color:#b66b00;font-size:.76rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em}.staff-order-alert__code{margin:8px 0 3px;font-size:1.65rem;font-weight:900;color:#17332d}.staff-order-alert__grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.staff-order-alert__card,.staff-order-alert__items,.staff-order-alert__note{padding:15px;border:1px solid #dfeae7;border-radius:15px;background:#fbfefd}.staff-order-alert__label{margin-bottom:4px;color:#74837f;font-size:.72rem;font-weight:900;text-transform:uppercase}.staff-order-alert__value{color:#18322b;font-weight:800;white-space:pre-line}.staff-order-alert__muted{color:#6f7f7a;font-size:.82rem;white-space:pre-line}
.staff-order-alert__item{display:flex;justify-content:space-between;gap:14px;padding:11px 0;border-bottom:1px solid #e5eeeb}.staff-order-alert__item:last-child{border-bottom:0}.staff-order-alert__enable{position:fixed;right:22px;bottom:22px;z-index:10990;display:flex;align-items:center;gap:8px;padding:11px 16px;border:0;border-radius:999px;background:#d97706;color:#fff;font-weight:800;box-shadow:0 12px 30px rgba(180,105,0,.3)}.staff-order-alert__enable[hidden]{display:none}
@media(max-width:620px){.staff-order-alert{padding:10px}.staff-order-alert__dialog{max-height:calc(100vh - 20px)}.staff-order-alert__header,.staff-order-alert__body{padding:17px}.staff-order-alert__grid{grid-template-columns:1fr}}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('staffNewOrderAlert');
    const enableButton = document.getElementById('staffNewOrderAlertEnable');
    if (!root || !enableButton) return;
    const pendingUrl = @json(route('staff.orders.pending-alerts'));
    const branchId = Number(@json((int) auth()->user()->branch_id));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const enabledKey = `chilldrink_staff_alerts_enabled_${branchId}`;
    const snoozeKey = `chilldrink_staff_alert_snooze_${branchId}`;
    let activeOrder = null;
    let requestBusy = false;
    let refreshQueued = false;
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const enabled = () => localStorage.getItem(enabledKey) !== '0';
    const snoozes = () => { try { return JSON.parse(localStorage.getItem(snoozeKey) || '{}'); } catch { return {}; } };
    const setEnabled = value => { localStorage.setItem(enabledKey, value ? '1' : '0'); root.hidden = true; enableButton.hidden = value; if (value) loadPending(true); };
    const setText = (selector, value) => { const element = root.querySelector(selector); if (element) element.textContent = value || ''; };
    const itemSummary = item => [item.size_name ? `Size ${item.size_name}` : null, Number.isFinite(Number(item.sugar_level)) ? `${item.sugar_level}% đường` : null, Number.isFinite(Number(item.ice_level)) ? `${item.ice_level}% đá` : null, Array.isArray(item.toppings) && item.toppings.length ? `Topping: ${item.toppings.join(', ')}` : null, item.item_note ? `Ghi chú: ${item.item_note}` : null].filter(Boolean).join(' · ');

    const render = order => {
        activeOrder = order;
        setText('[data-alert-code]', order.order_code || `#${order.order_id}`);
        setText('[data-alert-created]', `Tạo lúc ${order.created_at || 'Vừa xong'} · ${order.branch_name || 'Chi nhánh'}`);
        setText('[data-alert-customer]', order.customer_name || 'Khách hàng');
        setText('[data-alert-contact]', [order.customer_phone || 'Chưa có số điện thoại', order.customer_email || 'Không có email'].join('\n'));
        setText('[data-alert-payment]', order.payment_method_label || order.payment_method || 'Chưa rõ');
        setText('[data-alert-payment-status]', order.payment_status_label || order.payment_status || 'Chưa rõ');
        setText('[data-alert-total]', order.total_formatted || ''); setText('[data-alert-address]', order.shipping_address || 'Chưa có địa chỉ');
        const items = Array.isArray(order.items) ? order.items : [];
        setText('[data-alert-item-count]', `${items.length} món trong đơn`);
        root.querySelector('[data-alert-items]').innerHTML = items.length ? items.map(item => `<li class="staff-order-alert__item"><div><div class="staff-order-alert__value">${escapeHtml(item.quantity)}x ${escapeHtml(item.product_name || 'Sản phẩm')}</div><div class="staff-order-alert__muted">${escapeHtml(itemSummary(item) || 'Tùy chọn mặc định')}</div></div><div class="staff-order-alert__value text-end">${escapeHtml(item.total_formatted || '')}</div></li>`).join('') : '<li class="staff-order-alert__muted">Chưa có chi tiết món.</li>';
        const noteWrap = root.querySelector('[data-alert-note-wrap]'); noteWrap.classList.toggle('d-none', !order.note); setText('[data-alert-note]', order.note);
        const block = root.querySelector('[data-alert-block]'); block.classList.toggle('d-none', order.can_confirm || !order.confirm_block_reason); block.textContent = order.confirm_block_reason || '';
        root.querySelector('[data-alert-confirm]').disabled = !order.can_confirm; root.querySelector('[data-alert-open]').href = order.url || '#'; root.hidden = false;
    };

    async function loadPending(forceShow = false) {
        if (!enabled()) return;
        if (requestBusy) { refreshQueued = refreshQueued || forceShow; return; }
        requestBusy = true;
        try {
            const response = await fetch(pendingUrl, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',cache:'no-store'});
            if (!response.ok) return;
            const data = await response.json(); const now = Date.now(); const muted = snoozes();
            const order = (data.orders || []).find(candidate => Number(candidate.branch_id) === branchId && Number(muted[candidate.order_id] || 0) <= now);
            if (order && (forceShow || root.hidden)) render(order); else if (!order) root.hidden = true;
        } catch (error) { console.warn('[Staff order alert] Không tải được đơn chờ.', error); } finally {
            requestBusy = false;
            if (refreshQueued) { refreshQueued = false; queueMicrotask(() => loadPending(true)); }
        }
    }

    root.querySelector('[data-alert-disable]').addEventListener('click', () => setEnabled(false)); enableButton.addEventListener('click', () => setEnabled(true));
    root.querySelector('[data-alert-snooze]').addEventListener('click', () => { if (!activeOrder) return; const values = snoozes(); values[activeOrder.order_id] = Date.now() + 5 * 60 * 1000; localStorage.setItem(snoozeKey, JSON.stringify(values)); root.hidden = true; loadPending(true); });
    root.querySelector('[data-alert-confirm]').addEventListener('click', async function () {
        if (!activeOrder?.status_update_url || !activeOrder.can_confirm) return;
        const original = this.innerHTML; this.disabled = true; this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xác nhận';
        try {
            const response = await fetch(activeOrder.status_update_url, {method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams({_method:'PUT',status:'confirmed'}).toString()});
            const data = await response.json().catch(() => ({})); if (!response.ok || data.success === false) throw new Error(data.message || 'Không thể xác nhận đơn hàng.');
            root.hidden = true; window.showRealtimeToast?.(data.message || 'Đã xác nhận đơn hàng.', 'success'); await loadPending(true);
        } catch (error) { window.showRealtimeToast?.(error.message, 'warning'); this.disabled = false; this.innerHTML = original; }
    });
    document.addEventListener('order:created', event => { if (Number(event.detail?.branch_id) === branchId) loadPending(true); });
    document.addEventListener('order:status-updated', event => {
        if (!activeOrder || Number(event.detail?.order_id) !== Number(activeOrder.order_id)) return;
        if (String(event.detail?.status || '') === 'pending') return;
        activeOrder = null;
        root.hidden = true;
        loadPending(true);
    });
    enableButton.hidden = enabled(); loadPending(true);
});
</script>
@endif
