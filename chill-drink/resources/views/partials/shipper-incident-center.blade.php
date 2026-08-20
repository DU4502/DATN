@php
    $incidentUser = auth()->user();
    $incidentRootMode = $incidentUser?->isSuperAdmin() && ! $incidentUser?->isViewingAdminWorkspace();
    $incidentFeedUrl = $incidentRootMode
        ? route('admin.super-admin.manage.shipper-incidents.feed')
        : route('admin.shipper-incidents.feed');
    $incidentIndexUrl = $incidentRootMode
        ? route('admin.super-admin.manage.shipper-incidents.index')
        : route('admin.shipper-incidents.index');
    $incidentChannel = $incidentRootMode
        ? 'super-admin-incidents'
        : (is_numeric($incidentUser?->branch_id) ? 'branch-admin-incidents.'.(int) $incidentUser->branch_id : null);
@endphp

<div class="dropdown" data-shipper-incident-center
     data-feed-url="{{ $incidentFeedUrl }}"
     data-index-url="{{ $incidentIndexUrl }}"
     data-channel="{{ $incidentChannel }}">
    <button type="button"
            class="btn btn-sm position-relative d-inline-flex align-items-center justify-content-center"
            style="width:38px;height:38px;border-radius:12px;border:1px solid rgba(245,158,11,.35);background:#fff7ed;color:#b45309;"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="Sự cố giao vận">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span data-incident-badge
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="display:none;font-size:.65rem;min-width:19px;">0</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width:min(390px,calc(100vw - 24px));border-radius:16px;overflow:hidden;">
        <div class="px-3 py-3 border-bottom d-flex align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-bold">Sự cố giao vận</div>
                <div class="small text-secondary" data-incident-subtitle>Đang kiểm tra...</div>
            </div>
            <a href="{{ $incidentIndexUrl }}" class="btn btn-sm btn-outline-warning">Quản lý</a>
        </div>
        <div data-incident-list style="max-height:360px;overflow:auto;">
            <div class="p-3 text-secondary small">Đang tải sự cố...</div>
        </div>
    </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-shipper-incident-center]').forEach(function (center) {
        if (center.dataset.bound === '1') return;
        center.dataset.bound = '1';

        const feedUrl = center.dataset.feedUrl;
        const indexUrl = center.dataset.indexUrl;
        const channelName = center.dataset.channel || '';
        const badge = center.querySelector('[data-incident-badge]');
        const list = center.querySelector('[data-incident-list]');
        const subtitle = center.querySelector('[data-incident-subtitle]');
        let booted = false;
        let latestIncidentId = 0;
        let busy = false;

        const esc = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        function toast(message) {
            if (typeof window.showRealtimeToast === 'function') {
                window.showRealtimeToast(message, 'warning');
                return;
            }

            const el = document.createElement('div');
            el.className = 'alert alert-warning shadow position-fixed end-0 m-3';
            el.style.cssText = 'top:72px;z-index:10050;max-width:420px;border-radius:14px;';
            el.innerHTML = `<strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Sự cố giao vận</strong><div class="small mt-1">${esc(message)}</div>`;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 7000);
        }

        function render(data) {
            const incidents = Array.isArray(data.incidents) ? data.incidents : [];
            const count = Number(data.count || 0);

            if (badge) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.style.display = count > 0 ? '' : 'none';
            }
            if (subtitle) {
                subtitle.textContent = count > 0 ? `${count} sự cố đang chờ xử lý` : 'Không có sự cố đang chờ';
            }
            if (!list) return;

            if (!incidents.length) {
                list.innerHTML = '<div class="p-4 text-center text-secondary small"><i class="bi bi-check-circle text-success d-block fs-4 mb-2"></i>Không có sự cố đang chờ xử lý.</div>';
                return;
            }

            list.innerHTML = incidents.slice(0, 8).map((item) => `
                <a href="${esc(indexUrl)}?q=${encodeURIComponent(item.order_code || item.order_id || '')}"
                   class="dropdown-item-text text-decoration-none text-dark d-block px-3 py-3 border-bottom"
                   style="white-space:normal;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-circle-fill text-warning mt-1"></i>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold small">${esc(item.order_code || ('#' + item.order_id))} · ${esc(item.shipper_name || 'Shipper')}</div>
                            <div class="small text-secondary mt-1">${esc(item.branch_name || '')}</div>
                            <div class="small mt-1">${esc(item.description || 'Shipper báo sự cố.')}</div>
                            <div class="small text-muted mt-1">${esc(item.reported_at_label || '')}</div>
                        </div>
                    </div>
                </a>
            `).join('');
        }

        async function refresh(showNewToast = true) {
            if (!feedUrl || busy) return;
            busy = true;
            try {
                const response = await fetch(feedUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const data = await response.json();
                const currentLatest = Number(data.latest_incident_id || 0);
                const incidents = Array.isArray(data.incidents) ? data.incidents : [];

                if (booted && showNewToast && currentLatest > latestIncidentId) {
                    const newest = incidents
                        .filter((item) => Number(item.incident_id || 0) > latestIncidentId)
                        .sort((a, b) => Number(b.incident_id || 0) - Number(a.incident_id || 0))[0];
                    if (newest) {
                        toast(`${newest.order_code || ('#' + newest.order_id)} · ${newest.shipper_name || 'Shipper'}: ${newest.description || 'Báo sự cố.'}`);
                    }
                }

                latestIncidentId = Math.max(latestIncidentId, currentLatest);
                booted = true;
                render(data);
            } catch (error) {
                console.warn('Không thể cập nhật sự cố shipper realtime.', error);
            } finally {
                busy = false;
            }
        }

        refresh(false);
        window.setInterval(() => {
            if (!document.hidden) refresh(true);
        }, 4000);

        // Websocket giúp báo ngay; polling 4 giây là fallback bắt buộc cho local/XAMPP
        // khi Reverb/Pusher chưa được bật.
        if (channelName && window.Echo) {
            try {
                window.Echo.private(channelName)
                    .listen('.shipper.incident.reported', function (payload) {
                        const incomingId = Number(payload.incident_id || 0);
                        if (!booted || incomingId > latestIncidentId) {
                            toast(`${payload.order_code || ('#' + payload.order_id)} · ${payload.shipper_name || 'Shipper'}: ${payload.description || 'Báo sự cố.'}`);
                        }
                        latestIncidentId = Math.max(latestIncidentId, incomingId);
                        refresh(false);
                    });
            } catch (error) {
                console.warn('Không thể subscribe kênh sự cố shipper.', error);
            }
        }
    });
});
</script>
@endonce
