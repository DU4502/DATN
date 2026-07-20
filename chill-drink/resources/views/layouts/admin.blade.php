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
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="admin-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="admin-logo-mark" style="object-fit: contain; padding: 2px;">
                <span>
                    <span class="admin-logo-title d-block">Chill Drink</span>
                    <span class="admin-logo-subtitle">Quản trị hệ thống</span>
                </span>
            </a>

            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Tổng quát</a>
                <a href="{{ route('admin.vouchers.index') }}" class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}"><i class="bi bi-ticket-perforated"></i> Phiếu ưu đãi</a>
                <a href="{{ route('admin.toppings.index') }}" class="nav-link {{ request()->routeIs('admin.toppings.*') ? 'active' : '' }}"><i class="bi bi-egg-fried"></i> Topping</a>
                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i class="bi bi-cup-hot"></i> Sản phẩm</a>
                <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-folder2"></i> Danh mục</a>
                <a href="{{ route('admin.slides.index') }}" class="nav-link {{ request()->routeIs('admin.slides.*') ? 'active' : '' }}"><i class="bi bi-images"></i> Trình chiếu</a>
                <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Đơn hàng</a>
                <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="bi bi-chat-square-text"></i> Đánh giá</a>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Khách hàng</a>
                @if(auth()->user()?->isSuperAdmin())
                    <a href="{{ route('admin.super-admin') }}" class="nav-link {{ request()->routeIs('admin.super-admin') ? 'active' : '' }}"><i class="bi bi-shield-lock-fill"></i> Quản trị cấp cao</a>
                @endif
                <a href="{{ route('admin.chat.index') }}" class="nav-link {{ request()->routeIs('admin.chat.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots"></i> Chat CSKH
                    @php
                        $unreadChatMessages = auth()->user()?->unreadConversationMessagesCount() ?? 0;
                    @endphp
                    <span id="sidebar-chat-badge" class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.72rem;{{ $unreadChatMessages > 0 ? '' : 'display:none;' }}">{{ $unreadChatMessages > 99 ? '99+' : $unreadChatMessages }}</span>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
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
</body>
</html>
