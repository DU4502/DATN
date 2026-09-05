<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin - {{ config('app.name', 'Chill Drink') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/bootstrap-local.css', 'resources/js/app.js'])

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
            <button type="button" class="admin-sidebar-close" data-admin-sidebar-close aria-label="Đóng menu quản trị">
                <i class="bi bi-x-lg"></i>
            </button>
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
                <a href="{{ route($currentAdminUser?->isCskh() ? 'admin.chat.order-issues.index' : 'admin.order-issues.index') }}" class="nav-link {{ request()->routeIs('admin.order-issues.*', 'admin.chat.order-issues.*') ? 'active' : '' }}"><i class="bi bi-headset"></i> Yêu cầu hỗ trợ <span id="sidebar-order-issue-badge" class="badge rounded-pill bg-danger ms-auto" style="font-size:.72rem;{{ ($pendingOrderIssueCount ?? 0) > 0 ? '' : 'display:none;' }}">{{ min(99, $pendingOrderIssueCount ?? 0) }}</span></a>
            </nav>

            @if($adminPreviewMode)
                <div class="admin-sidebar-footer">
                    <a href="{{ route('admin.preview-admin.exit') }}" class="nav-link mb-1"><i class="bi bi-arrow-counterclockwise"></i> Quay lại cấp cao</a>
                </div>
            @endif
        </aside>
        <div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop></div>
        <div class="admin-content">
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button type="button" class="admin-mobile-toggle" data-admin-sidebar-toggle aria-label="Mở menu quản trị">
                        <i class="bi bi-list"></i>
                    </button>
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
                    <div class="dropdown" data-admin-account-menu>
                        <button type="button" class="admin-avatar border-0 p-0" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mở menu tài khoản" data-admin-avatar-toggle>
                            @if($adminAvatarUrl)
                                <img src="{{ $adminAvatarUrl }}" alt="{{ Auth::user()->name }}">
                            @else
                                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end admin-dropdown-menu mt-2">
                            <div class="px-3 py-2">
                                <strong class="d-block text-truncate" style="max-width:220px;">{{ Auth::user()->name }}</strong>
                                <span class="text-secondary small">{{ $currentAdminUser?->isSuperAdmin() ? 'Quản trị cấp cao' : 'Quản trị chi nhánh' }}</span>
                            </div>
                            <div class="dropdown-divider my-1"></div>
                            <a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-house-door"></i>Về trang chủ</a>
                            @if($adminPreviewMode)
                                <a class="dropdown-item" href="{{ route('admin.preview-admin.exit') }}"><i class="bi bi-arrow-counterclockwise"></i>Quay lại cấp cao</a>
                            @endif
                            <div class="dropdown-divider my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item danger text-danger"><i class="bi bi-box-arrow-right"></i>Đăng xuất</button>
                            </form>
                        </div>
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
    @include('partials.realtime')
    @include('partials.instant-actions')
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

        const adminSidebar = document.querySelector('.admin-sidebar');
        const adminSidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');
        const adminSidebarClose = document.querySelector('[data-admin-sidebar-close]');
        const adminSidebarBackdrop = document.querySelector('[data-admin-sidebar-backdrop]');

        const setAdminSidebarOpen = (open) => {
            if (!adminSidebar) return;
            adminSidebar.classList.toggle('open', open);
            adminSidebarBackdrop?.classList.toggle('show', open);
            document.body.classList.toggle('admin-sidebar-open', open);
            adminSidebarToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        adminSidebarToggle?.addEventListener('click', () => setAdminSidebarOpen(true));
        adminSidebarClose?.addEventListener('click', () => setAdminSidebarOpen(false));
        adminSidebarBackdrop?.addEventListener('click', () => setAdminSidebarOpen(false));
        adminSidebar?.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setAdminSidebarOpen(false));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setAdminSidebarOpen(false);
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) setAdminSidebarOpen(false);
        });
    </script>
    @include('partials.order-issue-notification-badge')
    @stack('scripts')
</body>
</html>
