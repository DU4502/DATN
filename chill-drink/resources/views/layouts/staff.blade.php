<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nhân viên - {{ config('app.name', 'Chill Drink') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/bootstrap-local.css', 'resources/js/app.js'])
    @include('admin.partials.styles')
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="{{ route('staff.dashboard') }}" class="admin-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="admin-logo-mark" style="object-fit:contain;padding:2px;">
            <span>
                <span class="admin-logo-title d-block">Chill Drink</span>
                <span class="admin-logo-subtitle">Nhân viên</span>
            </span>
        </a>

        <nav class="nav flex-column">
            <a href="{{ route('staff.dashboard') }}" class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Tổng quát
            </a>
            <a href="{{ route('staff.orders.index') }}" class="nav-link {{ request()->routeIs('staff.orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Đơn hàng
            </a>
            <a href="{{ route('staff.products.availability.index') }}" class="nav-link {{ request()->routeIs('staff.products.availability.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Tình trạng sản phẩm
            </a>
            <a href="{{ route('staff.group-orders.index') }}" class="nav-link {{ request()->routeIs('staff.group-orders.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Đơn nhóm
            </a>
            <a href="{{ route('staff.chat.index') }}" class="nav-link {{ request()->routeIs('staff.chat.*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots"></i> Chat hỗ trợ
                @php
                    $unreadChatMessages = auth()->user()?->unreadConversationMessagesCount() ?? 0;
                @endphp
                <span id="sidebar-chat-badge" class="badge rounded-pill bg-danger ms-auto" style="font-size:0.72rem;{{ $unreadChatMessages > 0 ? '' : 'display:none;' }}">{{ $unreadChatMessages > 99 ? '99+' : $unreadChatMessages }}</span>
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
                <h1 class="h4 fw-bold mb-0" style="font-size:1rem;">@yield('page-title', 'Tổng quát')</h1>
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
                <span class="badge bg-warning text-dark" style="font-size:0.72rem;">NHÂN VIÊN</span>
                <span class="text-secondary fw-medium d-none d-lg-inline" style="font-size:0.8125rem;">{{ Auth::user()->name }}</span>
                @php
                    $staffAvatar = Auth::user()->avatar;
                    $staffAvatarIsImage = $staffAvatar && !str_starts_with($staffAvatar, 'preset-');
                    $staffAvatarUrl = $staffAvatarIsImage ? asset('storage/' . $staffAvatar) : null;
                @endphp
                <div class="admin-avatar" aria-label="Tài khoản">
                    @if($staffAvatarUrl)
                        <img src="{{ $staffAvatarUrl }}" alt="{{ Auth::user()->name }}">
                    @else
                        {{ mb_substr(Auth::user()->name, 0, 1) }}
                    @endif
                </div>
            </div>
        </header>

        <main class="admin-page">
            @if(session('success'))
                <div class="alert alert-success" style="border-radius:var(--radius-sm);font-size:0.8125rem;">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" style="border-radius:var(--radius-sm);font-size:0.8125rem;">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

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
        alert.innerHTML = `<div class="d-flex align-items-start gap-2"><i class="bi bi-bell-fill mt-1"></i><div class="flex-grow-1">${message}</div><button type="button" class="btn-close" aria-label="Đóng"></button></div>`;
        alert.querySelector('.btn-close')?.addEventListener('click', () => alert.remove());
        container.appendChild(alert);
        window.setTimeout(() => { alert.style.transition = 'opacity .3s ease'; alert.style.opacity = '0'; window.setTimeout(() => alert.remove(), 300); }, 6000);
    };
</script>
@include('partials.realtime')
<script>
    (function () {
        const badge = document.getElementById('sidebar-chat-badge');
        if (!badge) return;
        const unreadUrl = '{{ route('staff.chat.unread-count') }}';
        const updateChatBadge = async () => {
            try {
                const res = await fetch(unreadUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const data = await res.json();
                const count = data.count ?? 0;
                if (count > 0) { badge.textContent = count > 99 ? '99+' : count; badge.style.display = ''; }
                else { badge.style.display = 'none'; }
            } catch (e) {}
        };
        document.addEventListener('chat:messages-read', updateChatBadge);
        updateChatBadge();
        setInterval(() => { if (!document.hidden) updateChatBadge(); }, 5000);
    })();
</script>
</body>
</html>
