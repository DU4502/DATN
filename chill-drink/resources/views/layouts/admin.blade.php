<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin - {{ config('app.name', 'Chill Drink') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    @include('admin.partials.styles')
</head>
<body>
    @php
        $currentAdminUser = auth()->user();
        $adminPreviewMode = $currentAdminUser?->isViewingAdminWorkspace() ?? false;
        $adminPreviewBranchId = $currentAdminUser?->adminWorkspaceBranchId();
        $adminRouteParams = $adminPreviewMode && $adminPreviewBranchId
            ? ['branch_id' => $adminPreviewBranchId]
            : [];
    @endphp
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="{{ route('admin.dashboard', $adminRouteParams) }}" class="admin-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="admin-logo-mark" style="object-fit: contain; padding: 2px;">
                <span>
                    <span class="admin-logo-title d-block">Chill Drink</span>
                    <span class="admin-logo-subtitle">Quản trị hệ thống</span>
                </span>
            </a>

            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Tổng quát</a>
                <a href="{{ route('admin.vouchers.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}"><i class="bi bi-ticket-perforated"></i> Phiếu ưu đãi</a>
                <a href="{{ route('admin.toppings.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.toppings.*') ? 'active' : '' }}"><i class="bi bi-egg-fried"></i> Topping</a>
                <a href="{{ route('admin.products.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i class="bi bi-cup-hot"></i> Sản phẩm</a>
                <a href="{{ route('admin.categories.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-folder2"></i> Danh mục</a>
                <a href="{{ route('admin.orders.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Đơn hàng</a>
                <a href="{{ route('admin.shipper-incidents.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.shipper-incidents.*') ? 'active' : '' }}"><i class="bi bi-exclamation-triangle"></i> Sự cố giao vận</a>
                <a href="{{ route('admin.cod-settlements.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.cod-settlements.*') ? 'active' : '' }}"><i class="bi bi-cash-coin"></i> Đối soát COD</a>
                <a href="{{ route('admin.group-orders.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.group-orders.*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i> Đơn nhóm</a>
                <a href="{{ route('admin.reviews.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="bi bi-chat-square-text"></i> Đánh giá</a>
                <a href="{{ route('admin.users.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Khách hàng</a>
                <a href="{{ route('admin.staff.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}"><i class="bi bi-person-badge"></i> Quản lý Staff</a>
                @if($currentAdminUser?->isSuperAdmin() && ! $adminPreviewMode)
                    <a href="{{ route('admin.super-admin') }}" class="nav-link {{ request()->routeIs('admin.super-admin') ? 'active' : '' }}"><i class="bi bi-shield-lock-fill"></i> Quản trị cấp cao</a>
                @endif
                <a href="{{ route('admin.chat.index', $adminRouteParams) }}" class="nav-link {{ request()->routeIs('admin.chat.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots"></i> Chat CSKH
                    @php
                        $unreadChatMessages = $currentAdminUser?->unreadConversationMessagesCount() ?? 0;
                    @endphp
                    <span id="sidebar-chat-badge" class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.72rem;{{ $unreadChatMessages > 0 ? '' : 'display:none;' }}">{{ $unreadChatMessages > 99 ? '99+' : $unreadChatMessages }}</span>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                @if($adminPreviewMode)
                    <a href="{{ route('admin.preview-admin.exit') }}" class="nav-link mb-1"><i class="bi bi-arrow-counterclockwise"></i> Quay lại cấp cao</a>
                @endif
                <a href="{{ route('home') }}" class="nav-link mb-1"><i class="bi bi-arrow-left-square"></i> Về trang chủ</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary w-100 btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                    </button>
                </form>
            </div>
        </aside>
        <div class="admin-content">
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h1 class="h4 fw-bold mb-0" style="font-size: 1rem;">@yield('page-title', 'Tổng quát')</h1>
                    @if($adminPreviewMode)
                        <a href="{{ route('admin.preview-admin.exit') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại cấp cao
                        </a>
                    @endif


                    @unless(View::hasSection('hide-topbar-search'))
                        <form method="GET" action="@yield('topbar-search-action', url()->current())" class="admin-search" role="search">
                            @foreach(request()->except(['q', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <span class="admin-search-icon"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="@yield('search-placeholder', 'Tìm kiếm...')" aria-label="@yield('search-placeholder', 'Tìm kiếm...')">
                        </form>
                    @endunless

                </div>
                <div class="admin-topbar-actions">
                    @include('partials.shipper-incident-center')
                    <span class="text-secondary fw-medium d-none d-lg-inline" style="font-size: 0.8125rem;">{{ Auth::user()->name }}</span>
                    @php
                        $adminAvatar = Auth::user()->avatar;
                        $adminAvatarIsImage = $adminAvatar && ! str_starts_with($adminAvatar, 'preset-');
                        $adminAvatarUrl = $adminAvatarIsImage ? asset('storage/' . $adminAvatar) : null;
                    @endphp
                    <div class="admin-avatar" aria-label="Tài khoản">
                        @if($adminAvatarUrl)
                            <img src="{{ $adminAvatarUrl }}" alt="{{ Auth::user()->name }}">
                        @else
                            {{ mb_substr(Auth::user()->name, 0, 1) }}
                        @endif
                    </div>
                </div>
            </header>

            <main class="admin-page">
                @if(session('success'))
                    <div class="alert alert-success" style="border-radius: var(--radius-sm); font-size: 0.8125rem;">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger" style="border-radius: var(--radius-sm); font-size: 0.8125rem;">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            function cleanupOrphanModalBackdrop() {
                if (document.querySelector('.modal.show')) return;
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }

            window.addEventListener('pageshow', cleanupOrphanModalBackdrop);
            document.addEventListener('hidden.bs.modal', () => window.setTimeout(cleanupOrphanModalBackdrop, 80));
        })();
    </script>
    <script>
        window.showRealtimeToast = function (message, type = 'info') {
            const containerId = 'realtimeToastContainer';
            let container = document.getElementById(containerId);

            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:10001;width:360px;max-width:calc(100vw - 40px);display:flex;flex-direction:column;gap:10px;';
                document.body.appendChild(container);
            }

            const alert = document.createElement('div');
            const alertType = type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'primary');
            alert.className = `alert alert-${alertType} shadow-sm mb-0`;
            alert.style.borderRadius = '12px';
            alert.innerHTML = `
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-bell-fill mt-1"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" aria-label="Đóng"></button>
                </div>
            `;

            alert.querySelector('.btn-close')?.addEventListener('click', () => alert.remove());
            container.appendChild(alert);

            window.setTimeout(() => {
                alert.style.transition = 'opacity .3s ease';
                alert.style.opacity = '0';
                window.setTimeout(() => alert.remove(), 300);
            }, 6000);
        };
    </script>
    <script>
        (function () {
            const adminBranchId = @json($currentAdminUser?->isAdmin() && is_numeric($currentAdminUser?->branch_id) ? (int) $currentAdminUser->branch_id : null);
            const isBranchAdmin = @json($currentAdminUser?->isAdmin() ?? false);
            const adminOrdersUrl = @json(route('admin.orders.index', $adminRouteParams));
            const pendingAlertsUrl = @json(route('admin.orders.pending-alerts', $adminRouteParams));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const comboToggleKey = adminBranchId ? `chilldrink_admin_alerts_enabled_${adminBranchId}` : 'chilldrink_admin_alerts_enabled';
            const snoozeKey = adminBranchId ? `chilldrink_admin_alert_snooze_${adminBranchId}` : 'chilldrink_admin_alert_snooze';
            const pendingSoundKey = adminBranchId ? `chilldrink_admin_pending_alert_sound_${adminBranchId}` : 'chilldrink_admin_pending_alert_sound';
            const remindDelayMs = 5 * 60 * 1000;
            const pollIntervalMs = 10000;
            const bellLoopMs = 8000;
            const bellLoopMaxPlays = 3;
            let audioContext = null;
            let alertPollBusy = false;
            let alertPollTimer = null;
            let bellLoopTimer = null;
            let bellLoopPlayCount = 0;
            let lastAudioNoticeAt = 0;
            let activeAlertOrderId = null;
            let activeOrderSignature = '';
            let pendingOrders = [];
            let pendingCount = 0;

            if (!isBranchAdmin || !pendingAlertsUrl) {
                return;
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                })[char]);
            }

            function injectAdminAlertStyles() {
                if (document.getElementById('adminPendingAlertStyles')) {
                    return;
                }

                const style = document.createElement('style');
                style.id = 'adminPendingAlertStyles';
                style.textContent = `
                    #adminPendingAlertLayer {
                        position: fixed;
                        inset: 0;
                        z-index: 10040;
                        display: none;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                    }

                    #adminPendingAlertLayer.is-visible {
                        display: flex;
                    }

                    .admin-pending-alert-backdrop {
                        position: absolute;
                        inset: 0;
                        background: rgba(9, 19, 17, 0.58);
                        backdrop-filter: blur(3px);
                    }

                    .admin-pending-alert-modal {
                        position: relative;
                        width: min(760px, calc(100vw - 36px));
                        max-height: min(88vh, 920px);
                        overflow: auto;
                        background: #ffffff;
                        color: #15312b;
                        border-radius: 22px;
                        box-shadow: 0 30px 80px rgba(7, 29, 24, 0.28);
                        border: 1px solid rgba(17, 24, 39, 0.08);
                    }

                    .admin-pending-alert-header {
                        padding: 22px 24px 18px;
                        border-bottom: 1px solid rgba(148, 163, 184, 0.24);
                        background: linear-gradient(180deg, #f6fbfa 0%, #ffffff 100%);
                    }

                    .admin-pending-alert-body {
                        padding: 20px 24px 24px;
                    }

                    .admin-pending-alert-grid {
                        display: grid;
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                        gap: 14px;
                        margin-top: 18px;
                    }

                    .admin-pending-alert-card,
                    .admin-pending-alert-items,
                    .admin-pending-alert-note {
                        border: 1px solid #e6efec;
                        border-radius: 16px;
                        padding: 14px 16px;
                        background: #fbfefe;
                    }

                    .admin-pending-alert-label {
                        font-size: 0.72rem;
                        font-weight: 700;
                        letter-spacing: 0.04em;
                        text-transform: uppercase;
                        color: #6b7d78;
                        margin-bottom: 6px;
                    }

                    .admin-pending-alert-value {
                        font-size: 0.95rem;
                        font-weight: 600;
                        color: #102723;
                        line-height: 1.5;
                    }

                    .admin-pending-alert-muted {
                        color: #6a7d78;
                        font-size: 0.84rem;
                        line-height: 1.55;
                    }

                    .admin-pending-alert-items ul {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                    }

                    .admin-pending-alert-item {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto;
                        gap: 10px;
                        padding-bottom: 10px;
                        border-bottom: 1px dashed #d8e6e0;
                    }

                    .admin-pending-alert-item:last-child {
                        border-bottom: 0;
                        padding-bottom: 0;
                    }

                    .admin-pending-alert-actions {
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: space-between;
                        margin-top: 18px;
                    }

                    .admin-pending-alert-buttons {
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                    }

                    .admin-pending-alert-overlay-tag {
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        padding: 7px 12px;
                        border-radius: 999px;
                        background: #eef8f4;
                        color: #0f7259;
                        font-size: 0.82rem;
                        font-weight: 700;
                    }

                    .admin-pending-alert-toggle {
                        position: fixed;
                        right: 20px;
                        bottom: 20px;
                        z-index: 10030;
                        display: none;
                    }

                    .admin-pending-alert-toggle.is-visible {
                        display: block;
                    }

                    .admin-pending-alert-toggle button {
                        border: 0;
                        border-radius: 999px;
                        background: #0f9d77;
                        color: #fff;
                        box-shadow: 0 12px 28px rgba(15, 157, 119, 0.25);
                        padding: 12px 18px;
                        font-weight: 700;
                        font-size: 0.92rem;
                    }

                    .admin-pending-alert-topline {
                        display: flex;
                        gap: 14px;
                        align-items: flex-start;
                        justify-content: space-between;
                    }

                    .admin-pending-alert-code {
                        font-size: 1.45rem;
                        font-weight: 800;
                        line-height: 1.2;
                        margin-top: 8px;
                        color: #0f2f28;
                    }

                    .admin-pending-alert-badge {
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        padding: 8px 12px;
                        border-radius: 999px;
                        background: #ffefe4;
                        color: #ba5d14;
                        font-size: 0.82rem;
                        font-weight: 800;
                        white-space: nowrap;
                    }

                    .admin-pending-alert-warning {
                        margin-top: 14px;
                        border-radius: 14px;
                        background: #fff7ed;
                        border: 1px solid #fed7aa;
                        padding: 12px 14px;
                        color: #9a3412;
                        font-size: 0.88rem;
                        font-weight: 600;
                    }

                    .admin-pending-alert-header-actions {
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                        justify-content: flex-end;
                    }

                    @media (max-width: 768px) {
                        .admin-pending-alert-modal {
                            width: calc(100vw - 20px);
                            max-height: calc(100vh - 20px);
                            border-radius: 18px;
                        }

                        .admin-pending-alert-header,
                        .admin-pending-alert-body {
                            padding-left: 16px;
                            padding-right: 16px;
                        }

                        .admin-pending-alert-grid {
                            grid-template-columns: minmax(0, 1fr);
                        }

                        .admin-pending-alert-topline {
                            flex-direction: column;
                        }

                        .admin-pending-alert-header-actions,
                        .admin-pending-alert-actions,
                        .admin-pending-alert-buttons {
                            width: 100%;
                        }

                        .admin-pending-alert-buttons .btn {
                            flex: 1 1 100%;
                        }
                    }
                `;
                document.head.appendChild(style);
            }

            function getAlertLayer() {
                let layer = document.getElementById('adminPendingAlertLayer');
                if (layer) {
                    return layer;
                }

                layer = document.createElement('div');
                layer.id = 'adminPendingAlertLayer';
                layer.innerHTML = `
                    <div class="admin-pending-alert-backdrop"></div>
                    <section class="admin-pending-alert-modal" role="dialog" aria-modal="true" aria-labelledby="adminPendingAlertTitle"></section>
                `;
                document.body.appendChild(layer);
                return layer;
            }

            function getAlertModal() {
                return getAlertLayer().querySelector('.admin-pending-alert-modal');
            }

            function getToggleContainer() {
                let container = document.getElementById('adminPendingAlertToggle');
                if (container) {
                    return container;
                }

                container = document.createElement('div');
                container.id = 'adminPendingAlertToggle';
                container.className = 'admin-pending-alert-toggle';
                container.innerHTML = `
                    <button type="button">
                        <i class="bi bi-bell-fill me-2"></i>
                        <span data-toggle-label>Bật cảnh báo đơn</span>
                    </button>
                `;
                container.querySelector('button')?.addEventListener('click', function () {
                    setComboEnabled(true);
                    window.showRealtimeToast?.('Đã bật lại cảnh báo đơn cho chi nhánh.', 'success');
                });
                document.body.appendChild(container);
                return container;
            }

            function getComboEnabled() {
                return localStorage.getItem(comboToggleKey) !== '0';
            }

            function updateToggleButton() {
                const container = getToggleContainer();
                const label = container.querySelector('[data-toggle-label]');
                if (getComboEnabled()) {
                    container.classList.remove('is-visible');
                    return;
                }

                const suffix = pendingCount > 0 ? ` (${pendingCount})` : '';
                if (label) {
                    label.textContent = `Bật cảnh báo đơn${suffix}`;
                }
                container.classList.add('is-visible');
            }

            function setComboEnabled(enabled) {
                localStorage.setItem(comboToggleKey, enabled ? '1' : '0');
                updateToggleButton();

                if (!enabled) {
                    stopBellLoop();
                    hideAlertModal();
                    activeAlertOrderId = null;
                    activeOrderSignature = '';
                    return;
                }

                pollPendingAlerts(true);
            }

            function loadSnoozeMap() {
                try {
                    const parsed = JSON.parse(localStorage.getItem(snoozeKey) || '{}');
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (error) {
                    return {};
                }
            }

            function saveSnoozeMap(map) {
                try {
                    localStorage.setItem(snoozeKey, JSON.stringify(map));
                } catch (error) {
                    // bỏ qua lỗi localStorage
                }
            }

            function clearResolvedSnoozes(orders) {
                const activeIds = new Set((orders || []).map((order) => String(order.order_id || '')));
                const snoozes = loadSnoozeMap();
                let dirty = false;

                Object.keys(snoozes).forEach((orderId) => {
                    if (!activeIds.has(orderId)) {
                        delete snoozes[orderId];
                        dirty = true;
                    }
                });

                if (dirty) {
                    saveSnoozeMap(snoozes);
                }
            }

            function getSnoozedUntil(orderId) {
                const snoozes = loadSnoozeMap();
                return Number(snoozes[String(orderId)] || 0);
            }

            function snoozeOrder(orderId) {
                const snoozes = loadSnoozeMap();
                snoozes[String(orderId)] = Date.now() + remindDelayMs;
                saveSnoozeMap(snoozes);
            }

            function clearSnooze(orderId) {
                const snoozes = loadSnoozeMap();
                delete snoozes[String(orderId)];
                saveSnoozeMap(snoozes);
            }

            function getAudioContext() {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) {
                    return null;
                }

                audioContext ??= new AudioCtx();
                return audioContext;
            }

            async function playAdminNewOrderBell() {
                const context = getAudioContext();
                if (!context) {
                    return false;
                }

                if (context.state === 'suspended') {
                    await context.resume();
                }

                if (context.state !== 'running') {
                    return false;
                }

                const compressor = context.createDynamicsCompressor();
                compressor.threshold.setValueAtTime(-22, context.currentTime);
                compressor.knee.setValueAtTime(18, context.currentTime);
                compressor.ratio.setValueAtTime(8, context.currentTime);
                compressor.attack.setValueAtTime(0.004, context.currentTime);
                compressor.release.setValueAtTime(0.18, context.currentTime);
                compressor.connect(context.destination);

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
                [
                    1046.5, 1318.5, 1568.0, 1318.5,
                    1046.5, 1568.0, 1760.0, 1568.0,
                    1318.5, 1046.5
                ].forEach((frequency, index) => {
                    playBell(now + index * 0.32, frequency);
                });

                return true;
            }

            async function playPendingAlertSound(orderId = '') {
                try {
                    if (Date.now() - lastAudioNoticeAt < 2400) {
                        return false;
                    }

                    if (orderId) {
                        sessionStorage.setItem(pendingSoundKey, String(orderId));
                    }

                    const played = await playAdminNewOrderBell();
                    if (played) {
                        lastAudioNoticeAt = Date.now();
                        sessionStorage.removeItem(pendingSoundKey);
                    }

                    return played;
                } catch (error) {
                    return false;
                }
            }

            async function unlockAdminAudioAndReplayPendingAlert() {
                const pendingOrderId = sessionStorage.getItem(pendingSoundKey);
                if (!pendingOrderId) {
                    return;
                }

                try {
                    const context = getAudioContext();
                    if (context?.state === 'suspended') {
                        await context.resume();
                    }

                    await playPendingAlertSound(pendingOrderId);
                } catch (error) {
                    // bỏ qua lỗi audio
                }
            }

            function stopBellLoop() {
                if (bellLoopTimer) {
                    window.clearTimeout(bellLoopTimer);
                    bellLoopTimer = null;
                }
                bellLoopPlayCount = 0;
            }

            function startBellLoop(orderId) {
                stopBellLoop();
                if (!orderId || !getComboEnabled()) {
                    return;
                }

                const scheduleNextBell = () => {
                    if (!getComboEnabled() || String(activeAlertOrderId || '') !== String(orderId)) {
                        stopBellLoop();
                        return;
                    }

                    if (bellLoopPlayCount >= bellLoopMaxPlays) {
                        stopBellLoop();
                        return;
                    }

                    bellLoopPlayCount += 1;
                    void playPendingAlertSound(orderId);

                    if (bellLoopPlayCount >= bellLoopMaxPlays) {
                        stopBellLoop();
                        return;
                    }

                    bellLoopTimer = window.setTimeout(scheduleNextBell, bellLoopMs);
                };

                scheduleNextBell();
            }

            function hideAlertModal() {
                const layer = getAlertLayer();
                layer.classList.remove('is-visible');
                getAlertModal().innerHTML = '';
            }

            function itemSummary(item) {
                const chunks = [];
                if (item.size_name) {
                    chunks.push(`Size ${item.size_name}`);
                }
                if (Number.isFinite(Number(item.sugar_level))) {
                    chunks.push(`${item.sugar_level}% đường`);
                }
                if (Number.isFinite(Number(item.ice_level))) {
                    chunks.push(`${item.ice_level}% đá`);
                }
                return chunks.join(' · ');
            }

            function orderSignature(order) {
                return JSON.stringify([
                    order?.order_id || 0,
                    order?.status || '',
                    order?.can_confirm ? 1 : 0,
                    order?.confirm_block_reason || '',
                    pendingCount,
                ]);
            }

            function renderAlertModal(order) {
                const modal = getAlertModal();
                const orderCode = order.order_code || `#${order.order_id || ''}`;
                const items = Array.isArray(order.items) ? order.items : [];
                const customerPhone = order.customer_phone || 'Chưa có số điện thoại';
                const customerEmail = order.customer_email || 'Không có email';
                const orderUrl = order.url || adminOrdersUrl;
                const pendingText = pendingCount > 1
                    ? `Còn ${pendingCount} đơn đang chờ xác nhận`
                    : '1 đơn đang chờ xác nhận';
                const noteHtml = order.note
                    ? `
                        <section class="admin-pending-alert-note">
                            <div class="admin-pending-alert-label">Ghi chú khách</div>
                            <div class="admin-pending-alert-value">${escapeHtml(order.note)}</div>
                        </section>
                    `
                    : '';
                const blockReasonHtml = !order.can_confirm && order.confirm_block_reason
                    ? `<div class="admin-pending-alert-warning">${escapeHtml(order.confirm_block_reason)}</div>`
                    : '';
                const itemRows = items.length > 0
                    ? items.map((item) => `
                        <li class="admin-pending-alert-item">
                            <div>
                                <div class="admin-pending-alert-value">${escapeHtml(item.quantity)}x ${escapeHtml(item.product_name || 'Sản phẩm')}</div>
                                <div class="admin-pending-alert-muted">${escapeHtml(itemSummary(item)) || 'Tùy chọn mặc định'}</div>
                            </div>
                            <div class="admin-pending-alert-value text-end">${escapeHtml(item.total_formatted || item.unit_price_formatted || '')}</div>
                        </li>
                    `).join('')
                    : '<li class="admin-pending-alert-muted">Chưa có chi tiết món.</li>';

                modal.innerHTML = `
                    <div class="admin-pending-alert-header">
                        <div class="admin-pending-alert-topline">
                            <div>
                                <div class="admin-pending-alert-overlay-tag">
                                    <i class="bi bi-bell-fill"></i>
                                    Cảnh báo đơn mới
                                </div>
                                <div id="adminPendingAlertTitle" class="admin-pending-alert-code">${escapeHtml(orderCode)}</div>
                                <div class="admin-pending-alert-muted" style="margin-top:6px;">
                                    Tạo lúc ${escapeHtml(order.created_at || 'Vừa xong')} · ${escapeHtml(order.branch_name || 'Chi nhánh')}
                                </div>
                            </div>
                            <div class="admin-pending-alert-header-actions">
                                <span class="admin-pending-alert-badge">
                                    <i class="bi bi-hourglass-split"></i>
                                    ${escapeHtml(pendingText)}
                                </span>
                                <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" data-alert-toggle>
                                    <i class="bi bi-bell-slash me-1"></i>Tắt cảnh báo
                                </button>
                            </div>
                        </div>
                        ${blockReasonHtml}
                    </div>
                    <div class="admin-pending-alert-body">
                        <div class="admin-pending-alert-grid">
                            <section class="admin-pending-alert-card">
                                <div class="admin-pending-alert-label">Khách hàng</div>
                                <div class="admin-pending-alert-value">${escapeHtml(order.customer_name || 'Khách hàng')}</div>
                                <div class="admin-pending-alert-muted" style="margin-top:6px;">${escapeHtml(customerPhone)}<br>${escapeHtml(customerEmail)}</div>
                            </section>
                            <section class="admin-pending-alert-card">
                                <div class="admin-pending-alert-label">Thanh toán</div>
                                <div class="admin-pending-alert-value">${escapeHtml(order.payment_method_label || order.payment_method || 'Chưa rõ')}</div>
                                <div class="admin-pending-alert-muted" style="margin-top:6px;">
                                    ${escapeHtml(order.payment_status_label || order.payment_status || 'Chưa rõ')}<br>
                                    Tổng tiền: <strong>${escapeHtml(order.total_formatted || '')}</strong>
                                </div>
                            </section>
                        </div>

                        <section class="admin-pending-alert-card" style="margin-top:14px;">
                            <div class="admin-pending-alert-label">Địa chỉ giao hàng</div>
                            <div class="admin-pending-alert-value">${escapeHtml(order.shipping_address || 'Chưa có địa chỉ')}</div>
                        </section>

                        <section class="admin-pending-alert-items" style="margin-top:14px;">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                <div>
                                    <div class="admin-pending-alert-label mb-1">Chi tiết món</div>
                                    <div class="admin-pending-alert-muted">${items.length} món trong đơn</div>
                                </div>
                                <a href="${escapeHtml(orderUrl)}" class="btn btn-outline-secondary btn-sm fw-semibold">Mở trang đơn</a>
                            </div>
                            <ul>${itemRows}</ul>
                        </section>

                        ${noteHtml}

                        <div class="admin-pending-alert-actions">
                            <div class="admin-pending-alert-buttons">
                                <button type="button" class="btn btn-success fw-semibold" data-alert-confirm ${order.can_confirm ? '' : 'disabled'}>
                                    <i class="bi bi-check2-circle me-1"></i>Xác nhận đơn
                                </button>
                                <button type="button" class="btn btn-outline-secondary fw-semibold" data-alert-snooze>
                                    <i class="bi bi-clock-history me-1"></i>Xem sau 5 phút
                                </button>
                            </div>
                            <div class="admin-pending-alert-muted">
                                Chuông sẽ lặp lại cho tới khi bạn xác nhận, xem sau hoặc tắt cảnh báo.
                            </div>
                        </div>
                    </div>
                `;

                modal.querySelector('[data-alert-confirm]')?.addEventListener('click', function () {
                    confirmOrderFromPopup(order, this);
                });
                modal.querySelector('[data-alert-snooze]')?.addEventListener('click', function () {
                    handleSnooze(order);
                });
                modal.querySelector('[data-alert-toggle]')?.addEventListener('click', function () {
                    setComboEnabled(false);
                    window.showRealtimeToast?.('Đã tắt cảnh báo đơn. Bạn có thể bật lại bằng nút nổi ở góc phải.', 'warning');
                });
            }

            function showAlertModal(order) {
                activeAlertOrderId = order.order_id;
                activeOrderSignature = orderSignature(order);
                renderAlertModal(order);
                getAlertLayer().classList.add('is-visible');
            }

            function getActiveAlertOrder() {
                return pendingOrders.find((order) => String(order.order_id || '') === String(activeAlertOrderId || '')) || null;
            }

            function getNextAlertOrder() {
                const now = Date.now();
                const activeOrder = getActiveAlertOrder();

                if (activeOrder && getSnoozedUntil(activeOrder.order_id) <= now) {
                    return activeOrder;
                }

                return pendingOrders.find((order) => getSnoozedUntil(order.order_id) <= now) || null;
            }

            function refreshAlertUI(forceRender = false) {
                updateToggleButton();

                if (!getComboEnabled()) {
                    stopBellLoop();
                    hideAlertModal();
                    return;
                }

                const nextOrder = getNextAlertOrder();
                if (!nextOrder) {
                    activeAlertOrderId = null;
                    activeOrderSignature = '';
                    stopBellLoop();
                    hideAlertModal();
                    return;
                }

                const nextSignature = orderSignature(nextOrder);
                const orderChanged = String(activeAlertOrderId || '') !== String(nextOrder.order_id || '');
                const shouldRender = forceRender || orderChanged || activeOrderSignature !== nextSignature;

                if (shouldRender) {
                    showAlertModal(nextOrder);
                    if (orderChanged || forceRender) {
                        startBellLoop(nextOrder.order_id);
                    }
                    return;
                }

                if (!bellLoopTimer) {
                    startBellLoop(nextOrder.order_id);
                }
            }

            async function confirmOrderFromPopup(order, button) {
                if (!order?.status_update_url) {
                    return;
                }

                const originalHtml = button?.innerHTML || '';
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang xác nhận';
                }

                try {
                    const body = new URLSearchParams({
                        _method: 'PUT',
                        status: 'confirmed',
                    });
                    const response = await fetch(order.status_update_url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: body.toString(),
                    });

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Không thể xác nhận đơn hàng.');
                    }

                    clearSnooze(order.order_id);
                    activeAlertOrderId = null;
                    activeOrderSignature = '';
                    stopBellLoop();
                    hideAlertModal();
                    document.dispatchEvent(new CustomEvent('order:status-updated', {
                        detail: {
                            ...data,
                            order_id: order.order_id,
                        }
                    }));
                    window.showRealtimeToast?.(data.message || `Đã xác nhận đơn ${order.order_code || order.order_id}.`, 'success');
                    await pollPendingAlerts(true);
                } catch (error) {
                    window.showRealtimeToast?.(error.message || 'Không thể xác nhận đơn từ popup.', 'warning');
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = originalHtml;
                    }
                }
            }

            function handleSnooze(order) {
                snoozeOrder(order.order_id);
                activeAlertOrderId = null;
                activeOrderSignature = '';
                stopBellLoop();
                hideAlertModal();
                window.showRealtimeToast?.(`Đã nhắc lại đơn ${order.order_code || order.order_id} sau 5 phút.`, 'info');
                refreshAlertUI(true);
            }

            async function pollPendingAlerts(forceRender = false) {
                if (alertPollBusy) {
                    return;
                }

                alertPollBusy = true;

                try {
                    const response = await fetch(pendingAlertsUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    pendingOrders = Array.isArray(data.orders) ? data.orders : [];
                    pendingCount = Number.parseInt(String(data.pending_count || pendingOrders.length || 0), 10) || 0;
                    clearResolvedSnoozes(pendingOrders);
                    refreshAlertUI(forceRender);
                } catch (error) {
                    console.warn('Không thể kiểm tra đơn chờ xác nhận.', error);
                } finally {
                    alertPollBusy = false;
                }
            }

            injectAdminAlertStyles();
            getAlertLayer();
            getToggleContainer();
            updateToggleButton();

            document.addEventListener('order:created', function () {
                pollPendingAlerts(true);
            });

            window.addEventListener('pointerdown', unlockAdminAudioAndReplayPendingAlert, { passive: true });
            window.addEventListener('keydown', unlockAdminAudioAndReplayPendingAlert);
            window.addEventListener('storage', function (event) {
                if ([comboToggleKey, snoozeKey].includes(event.key || '')) {
                    pollPendingAlerts(true);
                }
            });

            pollPendingAlerts(true);
            alertPollTimer = window.setInterval(() => {
                pollPendingAlerts();
            }, pollIntervalMs);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    pollPendingAlerts(true);
                }
            });
        })();
    </script>
    @include('partials.realtime')
    <script>
        // Cập nhật badge chat sidebar mỗi 5 giây
        (function () {
            const badge = document.getElementById('sidebar-chat-badge');
            if (!badge) return;

            const unreadUrl = '{{ route('admin.chat.unread-count') }}';

            const updateChatBadge = async () => {
                try {
                    const res = await fetch(unreadUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const count = data.count ?? 0;
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                } catch (e) {
                    // bỏ qua lỗi mạng
                }
            };

            // Cập nhật ngay khi admin vừa đọc tin
            document.addEventListener('chat:messages-read', updateChatBadge);

            updateChatBadge();
            setInterval(() => {
                if (!document.hidden) updateChatBadge();
            }, 5000);
        })();
    </script>
    <script>
        document.querySelectorAll('[data-image-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const target = document.querySelector(input.dataset.previewTarget);
                const file = input.files && input.files[0];

                if (!target || !file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    target.innerHTML = `<img src="${event.target.result}" alt="Xem trước ảnh sản phẩm">`;
                };
                reader.readAsDataURL(file);
            });
        });

        document.querySelectorAll('[data-gallery-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const target = document.querySelector(input.dataset.previewTarget);
                const files = Array.from(input.files || []).slice(0, 6);

                if (!target) {
                    return;
                }

                target.innerHTML = '';

                files.forEach((file) => {
                    if (!file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.alt = 'Xem trước ảnh con';
                        target.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            });
        });

        const filterToggle = document.querySelector('[data-admin-filter-toggle]');
        const filterPanel = document.querySelector('[data-admin-filter-panel]');

        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', () => {
                const isHidden = filterPanel.classList.contains('d-none');
                filterPanel.classList.toggle('d-none', !isHidden ? true : false);
                filterToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
