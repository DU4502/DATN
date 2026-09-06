@if(auth()->user()?->isStaffOnly() && is_numeric(auth()->user()?->branch_id))
<div id="staffNewOrderAlert" class="staff-order-alert" hidden>
    <div class="staff-order-alert__backdrop"></div>
    <section class="staff-order-alert__dialog" role="dialog" aria-modal="true" aria-labelledby="staffNewOrderAlertTitle">
        <header class="staff-order-alert__header">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="staff-order-alert__tag"><i class="bi bi-bell-fill"></i>Thông báo đơn mới</div>
                    <h2 id="staffNewOrderAlertTitle" class="staff-order-alert__code" data-alert-code></h2>
                    <div class="staff-order-alert__muted" data-alert-created></div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" data-alert-disable><i class="bi bi-bell-slash me-1"></i>Tắt thông báo</button>
            </div>
            <div class="alert alert-warning mt-3 mb-0 d-none" data-alert-block></div>
        </header>
        <div class="staff-order-alert__body">
            <div class="staff-order-alert__grid">
                <section class="staff-order-alert__card">
                    <div class="staff-order-alert__label">Khách hàng</div>
                    <div class="staff-order-alert__value" data-alert-customer></div>
                    <div class="staff-order-alert__muted mt-1" data-alert-contact></div>
                </section>
                <section class="staff-order-alert__card">
                    <div class="staff-order-alert__label">Thanh toán</div>
                    <div class="staff-order-alert__value" data-alert-payment></div>
                    <div class="staff-order-alert__muted mt-1" data-alert-payment-status></div>
                    <div class="fw-bold text-primary mt-2" data-alert-total></div>
                </section>
            </div>
            <section class="staff-order-alert__card staff-order-alert__address">
                <div class="staff-order-alert__label">Địa chỉ giao hàng</div>
                <div class="staff-order-alert__value" data-alert-address></div>
            </section>
            <section class="staff-order-alert__items">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                    <div>
                        <div class="staff-order-alert__label">Chi tiết món</div>
                        <div class="staff-order-alert__muted" data-alert-item-count></div>
                    </div><a href="#" class="btn btn-outline-secondary btn-sm fw-semibold" data-alert-open>Mở trang đơn</a>
                </div>
                <ul class="list-unstyled mb-0" data-alert-items></ul>
            </section>
            <section class="staff-order-alert__note d-none" data-alert-note-wrap>
                <div class="staff-order-alert__label">Ghi chú khách</div>
                <div class="staff-order-alert__value" data-alert-note></div>
            </section>
        </div>
        <footer class="staff-order-alert__actions d-flex flex-wrap justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary fw-semibold" data-alert-snooze><i class="bi bi-clock-history me-1"></i>Xem sau 5 phút</button>
            <button type="button" class="btn btn-success fw-semibold" data-alert-confirm><i class="bi bi-check2-circle me-1"></i>Nhận / Xác nhận đơn</button>
        </footer>
    </section>
</div>
<button id="staffNewOrderAlertEnable" type="button" class="staff-order-alert__enable" hidden><i class="bi bi-bell-fill"></i><span>Bật thông báo đơn mới</span></button>

<style>
    .staff-order-alert {
        position: fixed;
        inset: 0;
        z-index: 11000;
        display: grid;
        place-items: center;
        padding: 18px
    }

    .staff-order-alert[hidden] {
        display: none
    }

    .staff-order-alert__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(9, 19, 17, .58);
        backdrop-filter: blur(3px)
    }

    .staff-order-alert__dialog {
        position: relative;
        display: flex;
        flex-direction: column;
        width: min(1120px, calc(100vw - 44px));
        max-height: min(90vh, 780px);
        max-height: min(90dvh, 780px);
        overflow: hidden;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 28px 80px rgba(0, 0, 0, .25)
    }

    .staff-order-alert__header {
        padding: 18px 22px 14px;
        background: linear-gradient(135deg, #fff8df, #f2fffb 58%, #fff);
        border-bottom: 1px solid #eee5cd
    }

    .staff-order-alert__body {
        display: grid;
        grid-template-columns: minmax(300px, .82fr) minmax(430px, 1.18fr);
        gap: 14px;
        min-height: 0;
        padding: 18px 22px 20px;
        overflow: auto;
        overscroll-behavior: contain
    }

    .staff-order-alert__tag {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #b66b00;
        font-size: .76rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .05em
    }

    .staff-order-alert__code {
        margin: 6px 0 2px;
        font-size: 1.45rem;
        font-weight: 900;
        color: #17332d
    }

    .staff-order-alert__grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px
    }

    .staff-order-alert__address,
    .staff-order-alert__note {
        grid-column: 1
    }

    .staff-order-alert__items {
        grid-column: 2;
        grid-row: 1 / span 3;
        min-height: 100%;
        overflow: auto
    }

    .staff-order-alert__actions {
        flex: 0 0 auto;
        padding: 14px 22px;
        border-top: 1px solid #d8e9e4;
        background: #fff;
        box-shadow: 0 -8px 20px rgba(23, 51, 45, .06)
    }

    .staff-order-alert__card,
    .staff-order-alert__items,
    .staff-order-alert__note {
        padding: 14px;
        border: 1px solid #d8e9e4;
        border-radius: 14px;
        background: linear-gradient(180deg, #fbfffd, #f6fbfa)
    }

    .staff-order-alert__items {
        background: #fbfffd
    }

    .staff-order-alert__label {
        margin-bottom: 4px;
        color: #74837f;
        font-size: .7rem;
        font-weight: 900;
        text-transform: uppercase
    }

    .staff-order-alert__value {
        color: #18322b;
        font-weight: 800;
        white-space: pre-line
    }

    .staff-order-alert__muted {
        color: #6f7f7a;
        font-size: .82rem;
        white-space: pre-line
    }

    .staff-order-alert__item {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid #e5eeeb
    }

    .staff-order-alert__item:last-child {
        border-bottom: 0
    }

    .staff-order-alert__item>.staff-order-alert__value {
        min-width: 108px
    }

    .staff-order-alert__enable {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 10990;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 16px;
        border: 0;
        border-radius: 999px;
        background: #d97706;
        color: #fff;
        font-weight: 800;
        box-shadow: 0 12px 30px rgba(180, 105, 0, .3)
    }

    .staff-order-alert__enable[hidden] {
        display: none
    }

    @media(max-width:860px) {
        .staff-order-alert {
            padding: 10px;
            align-items: start
        }

        .staff-order-alert__dialog {
            width: min(100%, calc(100vw - 20px));
            max-height: calc(100vh - 20px);
            max-height: calc(100dvh - 20px)
        }

        .staff-order-alert__header,
        .staff-order-alert__body {
            padding: 17px
        }

        .staff-order-alert__body {
            display: block;
            overflow: auto
        }

        .staff-order-alert__grid {
            grid-template-columns: 1fr
        }

        .staff-order-alert__address,
        .staff-order-alert__items,
        .staff-order-alert__note {
            margin-top: 12px
        }

        .staff-order-alert__actions {
            padding: 12px 17px
        }

        .staff-order-alert__items {
            max-height: none;
            overflow: visible
        }
    }
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
        const pendingSoundKey = `chilldrink_staff_pending_alert_sound_${branchId}`;
        let activeOrder = null;
        let requestBusy = false;
        let refreshQueued = false;
        let soundContext = null;
        let bellLoopTimer = null;
        let bellLoopPlayCount = 0;
        let lastAudioNoticeAt = 0;
        let lastAnnouncedOrderId = null;
        let confirmBusy = false;
        const pollIntervalMs = 5000;
        const bellLoopMs = 8000;
        const bellLoopMaxPlays = 3;
        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [char]));
        const enabled = () => localStorage.getItem(enabledKey) !== '0';
        const snoozes = () => {
            try {
                return JSON.parse(localStorage.getItem(snoozeKey) || '{}');
            } catch {
                return {};
            }
        };
        const activeSnoozeIds = () => {
            const now = Date.now();
            const values = snoozes();
            return Object.entries(values)
                .filter(([, expiresAt]) => Number(expiresAt || 0) > now)
                .map(([orderId]) => orderId)
                .slice(0, 30);
        };
        const setEnabled = value => {
            localStorage.setItem(enabledKey, value ? '1' : '0');
            root.hidden = true;
            enableButton.hidden = value;
            if (!value) stopBellLoop();
            if (value) loadPending(true);
        };
        const setText = (selector, value) => {
            const element = root.querySelector(selector);
            if (element) element.textContent = value || '';
        };
        const itemSummary = item => [item.size_name ? `Size ${item.size_name}` : null, Number.isFinite(Number(item.sugar_level)) ? `${item.sugar_level}% đường` : null, Number.isFinite(Number(item.ice_level)) ? `${item.ice_level}% đá` : null, Array.isArray(item.toppings) && item.toppings.length ? `Topping: ${item.toppings.join(', ')}` : null, item.item_note ? `Ghi chú: ${item.item_note}` : null].filter(Boolean).join(' · ');
        const getAudioContext = () => {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return null;
            soundContext = soundContext || new AudioContext();
            return soundContext;
        };
        const playNewOrderBell = async () => {
            const context = getAudioContext();
            if (!context) return false;
            if (context.state === 'suspended') await context.resume();
            if (context.state !== 'running') return false;
            const compressor = context.createDynamicsCompressor();
            compressor.threshold.setValueAtTime(-22, context.currentTime);
            compressor.knee.setValueAtTime(18, context.currentTime);
            compressor.ratio.setValueAtTime(8, context.currentTime);
            compressor.attack.setValueAtTime(0.004, context.currentTime);
            compressor.release.setValueAtTime(0.18, context.currentTime);
            const masterGain = context.createGain();
            masterGain.gain.setValueAtTime(0.2, context.currentTime);
            compressor.connect(masterGain);
            masterGain.connect(context.destination);
            const playBell = (start, frequency) => {
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.type = 'square';
                oscillator.frequency.setValueAtTime(frequency, start);
                oscillator.frequency.exponentialRampToValueAtTime(frequency * 1.18, start + 0.12);
                gain.gain.setValueAtTime(0.001, start);
                gain.gain.exponentialRampToValueAtTime(1.0, start + 0.014);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.42);
                oscillator.connect(gain);
                gain.connect(compressor);
                oscillator.start(start);
                oscillator.stop(start + 0.44);
            };
            const now = context.currentTime + 0.03;
            [1046.5, 1318.5, 1568.0, 1318.5, 1046.5, 1568.0, 1760.0, 1568.0, 1318.5, 1046.5]
            .forEach((frequency, index) => playBell(now + index * 0.32, frequency));
            return true;
        };
        const playPendingAlertSound = async (orderId = '') => {
            try {
                if (Date.now() - lastAudioNoticeAt < 2400) return false;
                if (orderId) sessionStorage.setItem(pendingSoundKey, String(orderId));
                const played = await playNewOrderBell();
                if (played) {
                    lastAudioNoticeAt = Date.now();
                    sessionStorage.removeItem(pendingSoundKey);
                }
                return played;
            } catch (error) {
                return false;
            }
        };
        const unlockStaffAudioAndReplayPendingAlert = async () => {
            const pendingOrderId = sessionStorage.getItem(pendingSoundKey);
            if (pendingOrderId) await playPendingAlertSound(pendingOrderId);
        };
        const stopBellLoop = () => {
            if (bellLoopTimer) window.clearTimeout(bellLoopTimer);
            bellLoopTimer = null;
            bellLoopPlayCount = 0;
        };
        const startBellLoop = orderId => {
            stopBellLoop();
            if (!orderId || !enabled()) return;
            const scheduleNextBell = () => {
                if (!enabled() || String(activeOrder?.order_id || '') !== String(orderId) || bellLoopPlayCount >= bellLoopMaxPlays) {
                    stopBellLoop();
                    return;
                }
                bellLoopPlayCount += 1;
                void playPendingAlertSound(orderId);
                if (bellLoopPlayCount < bellLoopMaxPlays) bellLoopTimer = window.setTimeout(scheduleNextBell, bellLoopMs);
            };
            scheduleNextBell();
        };

        const render = order => {
            const shouldNotify = Number(lastAnnouncedOrderId) !== Number(order.order_id);
            activeOrder = order;
            lastAnnouncedOrderId = order.order_id;
            setText('[data-alert-code]', order.order_code || `#${order.order_id}`);
            setText('[data-alert-created]', `Tạo lúc ${order.created_at || 'Vừa xong'} · ${order.branch_name || 'Chi nhánh'}`);
            setText('[data-alert-customer]', order.customer_name || 'Khách hàng');
            setText('[data-alert-contact]', [order.customer_phone || 'Chưa có số điện thoại', order.customer_email || 'Không có email'].join('\n'));
            setText('[data-alert-payment]', order.payment_method_label || order.payment_method || 'Chưa rõ');
            setText('[data-alert-payment-status]', order.payment_status_label || order.payment_status || 'Chưa rõ');
            setText('[data-alert-total]', order.total_formatted || '');
            setText('[data-alert-address]', order.shipping_address || 'Chưa có địa chỉ');
            const items = Array.isArray(order.items) ? order.items : [];
            setText('[data-alert-item-count]', `${items.length} món trong đơn`);
            root.querySelector('[data-alert-items]').innerHTML = items.length ? items.map(item => `<li class="staff-order-alert__item"><div><div class="staff-order-alert__value">${escapeHtml(item.quantity)}x ${escapeHtml(item.product_name || 'Sản phẩm')}</div><div class="staff-order-alert__muted">${escapeHtml(itemSummary(item) || 'Tùy chọn mặc định')}</div></div><div class="staff-order-alert__value text-end">${escapeHtml(item.total_formatted || '')}</div></li>`).join('') : '<li class="staff-order-alert__muted">Chưa có chi tiết món.</li>';
            const noteWrap = root.querySelector('[data-alert-note-wrap]');
            noteWrap.classList.toggle('d-none', !order.note);
            setText('[data-alert-note]', order.note);
            const block = root.querySelector('[data-alert-block]');
            block.classList.toggle('d-none', order.can_confirm || !order.confirm_block_reason);
            block.textContent = order.confirm_block_reason || '';
            root.querySelector('[data-alert-confirm]').disabled = !order.can_confirm;
            root.querySelector('[data-alert-open]').href = order.url || '#';
            root.hidden = false;
            if (shouldNotify) {
                if (document.hidden) {
                    sessionStorage.setItem(pendingSoundKey, String(order.order_id));
                } else {
                    startBellLoop(order.order_id);
                }
            }
        };

        async function loadPending(forceShow = false) {
            if (!enabled() || confirmBusy) return;
            if (requestBusy) {
                refreshQueued = refreshQueued || forceShow;
                return;
            }
            requestBusy = true;
            try {
                const url = new URL(pendingUrl, window.location.origin);
                activeSnoozeIds().forEach(orderId => url.searchParams.append('muted_order_ids[]', orderId));
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                if (!response.ok) return;
                const data = await response.json();
                const order = (data.orders || []).find(candidate => Number(candidate.branch_id) === branchId);
                if (order && (forceShow || root.hidden)) render(order);
                else if (!order) {
                    activeOrder = null;
                    root.hidden = true;
                    sessionStorage.removeItem(pendingSoundKey);
                    stopBellLoop();
                }
            } catch (error) {
                console.warn('[Staff order alert] Không tải được đơn chờ.', error);
            } finally {
                requestBusy = false;
                if (refreshQueued) {
                    refreshQueued = false;
                    queueMicrotask(() => loadPending(true));
                }
            }
        }

        root.querySelector('[data-alert-disable]').addEventListener('click', () => setEnabled(false));
        enableButton.addEventListener('click', () => setEnabled(true));
        ['pointerdown', 'keydown', 'touchstart'].forEach(eventName => window.addEventListener(eventName, () => {
            void unlockStaffAudioAndReplayPendingAlert();
        }, { once: true, passive: true }));
        root.querySelector('[data-alert-snooze]').addEventListener('click', () => {
            if (!activeOrder) return;
            const values = snoozes();
            values[activeOrder.order_id] = Date.now() + 5 * 60 * 1000;
            localStorage.setItem(snoozeKey, JSON.stringify(values));
            root.hidden = true;
            sessionStorage.removeItem(pendingSoundKey);
            stopBellLoop();
            loadPending(true);
        });
        root.querySelector('[data-alert-confirm]').addEventListener('click', async function() {
            if (confirmBusy || !activeOrder?.status_update_url || !activeOrder.can_confirm) return;
            confirmBusy = true;
            const original = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xác nhận';
            try {
                const response = await fetch(activeOrder.status_update_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        _method: 'PUT',
                        status: 'confirmed'
                    }).toString()
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.success === false) throw new Error(data.message || 'Không thể xác nhận đơn hàng.');
                if (data.data) {
                    window.updateStaffDashboardOrder?.(data.data);
                    document.dispatchEvent(new CustomEvent('order:status-updated', { detail: data.data }));
                }
                root.hidden = true;
                sessionStorage.removeItem(pendingSoundKey);
                stopBellLoop();
                document.querySelectorAll('[data-staff-flash]').forEach(alert => alert.remove());
                window.showRealtimeToast?.(data.message || 'Đã xác nhận đơn hàng.', 'success');
                await loadPending(true);
            } catch (error) {
                window.showRealtimeToast?.(error.message, 'warning');
                this.disabled = false;
                this.innerHTML = original;
            } finally {
                confirmBusy = false;
            }
        });
        document.addEventListener('order:created', event => {
            if (Number(event.detail?.branch_id) === branchId) loadPending(true);
        });
        document.addEventListener('order:status-updated', event => {
            if (!activeOrder || Number(event.detail?.order_id) !== Number(activeOrder.order_id)) return;
            if (String(event.detail?.status || '') === 'pending') return;
            activeOrder = null;
            root.hidden = true;
            sessionStorage.removeItem(pendingSoundKey);
            stopBellLoop();
            loadPending(true);
        });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                loadPending(true).finally(() => unlockStaffAudioAndReplayPendingAlert());
            }
        });
        window.addEventListener('focus', () => {
            loadPending(true).finally(() => unlockStaffAudioAndReplayPendingAlert());
        });
        window.setInterval(() => {
            loadPending(false);
        }, pollIntervalMs);
        enableButton.hidden = enabled();
        loadPending(true);
    });
</script>
@endif
