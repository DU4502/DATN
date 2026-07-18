<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quản trị cấp cao - {{ config('app.name', 'Chill Drink') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --root-sidebar: #1a1d1c;
            --root-sidebar-soft: #252927;
            --root-green: #20b486;
            --root-green-dark: #0d9373;
            --root-coral: #f97360;
            --root-bg: #f5f7f6;
            --root-surface: #ffffff;
            --root-ink: #151918;
            --root-muted: #69716e;
            --root-border: #e1e6e4;
            --root-sidebar-width: 272px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 84px;
        }

        body,
        button,
        input,
        select,
        textarea {
            margin: 0;
            font-family: 'Inter', sans-serif !important;
            letter-spacing: 0 !important;
        }

        body {
            min-height: 100vh;
            background: var(--root-bg);
            color: var(--root-ink);
            font-size: 14px;
        }

        .root-shell { min-height: 100vh; }

        .root-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1030;
            width: var(--root-sidebar-width);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            background: var(--root-sidebar);
            color: #fff;
        }

        .root-brand {
            min-height: 68px;
            padding: 0.55rem 0.6rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #fff;
            text-decoration: none;
        }

        .root-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: contain;
            padding: 3px;
            background: #fff;
        }

        .root-brand-name {
            display: block;
            font-size: 0.92rem;
            font-weight: 800;
        }

        .root-brand-mode {
            display: block;
            margin-top: 0.12rem;
            color: #9ddfca;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .root-access {
            margin: 0.9rem 0.4rem 0.5rem;
            padding: 0.65rem 0.7rem;
            border: 1px solid rgba(32,180,134,0.28);
            border-radius: 7px;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            background: rgba(32,180,134,0.1);
        }

        .root-access-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--root-green);
            color: #10241e;
        }

        .root-access strong { display: block; font-size: 0.72rem; }
        .root-access span { color: #aeb7b3; font-size: 0.62rem; }

        .root-nav {
            flex: 1 1 auto;
            min-height: 0;
            margin-top: 0.45rem;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .root-nav::-webkit-scrollbar {
            width: 3px;
        }

        .root-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .root-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 999px;
        }

        .root-nav-label {
            margin: 1rem 0.65rem 0.35rem;
            color: #7f8985;
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .root-nav-link {
            min-height: 42px;
            margin: 0.12rem 0;
            padding: 0.62rem 0.7rem;
            border-radius: 7px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            color: #c7cecb;
            font-size: 0.76rem;
            font-weight: 650;
            text-decoration: none;
            transition: background 0.18s ease, color 0.18s ease;
        }

        .root-nav-link i {
            width: 20px;
            color: #8d9893;
            font-size: 1rem;
            text-align: center;
        }

        .root-nav-link:hover {
            background: var(--root-sidebar-soft);
            color: #fff;
        }

        .root-nav-link.active {
            background: var(--root-green);
            color: #10241e;
        }

        .root-nav-link.active i { color: #10241e; }

        .root-nav-badge {
            margin-left: auto;
            min-width: 20px;
            height: 20px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--root-coral);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 800;
        }

        .root-sidebar-footer {
            margin-top: auto;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            flex: 0 0 auto;
        }

        .root-logout {
            width: 100%;
            min-height: 40px;
            border: 1px solid rgba(249,115,96,0.3);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            background: rgba(249,115,96,0.08);
            color: #ffb0a4;
            font-size: 0.72rem;
            font-weight: 750;
        }

        .root-logout:hover {
            border-color: var(--root-coral);
            background: var(--root-coral);
            color: #fff;
        }

        .root-session {
            padding: 0.55rem 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .root-session-avatar {
            width: 34px;
            height: 34px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f3b25e;
            color: #3c2607;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .root-session strong { display: block; color: #fff; font-size: 0.7rem; }
        .root-session span { display: block; color: #8f9995; font-size: 0.6rem; }

        .root-content {
            min-height: 100vh;
            margin-left: var(--root-sidebar-width);
            display: flex;
            flex-direction: column;
        }

        .root-topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            min-height: 68px;
            padding: 0 1.5rem;
            border-bottom: 1px solid var(--root-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: rgba(255,255,255,0.96);
        }

        .root-topbar-left,
        .root-topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .root-mobile-toggle {
            width: 38px;
            height: 38px;
            border: 1px solid var(--root-border);
            border-radius: 7px;
            display: none;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .root-breadcrumb {
            color: var(--root-muted);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .root-breadcrumb strong { color: var(--root-ink); }

        .root-live {
            min-height: 30px;
            padding: 0.35rem 0.6rem;
            border: 1px solid #c9eadf;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f1fbf7;
            color: #147858;
            font-size: 0.66rem;
            font-weight: 800;
        }

        .root-live::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #16a34a;
        }

        .root-topbar-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--root-border);
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: var(--root-muted);
        }

        .root-topbar-search {
            width: min(320px, 28vw);
            height: 38px;
            border: 1px solid var(--root-border);
            border-radius: 7px;
            display: flex;
            align-items: center;
            background: #fff;
            overflow: hidden;
        }

        .root-topbar-search i { margin-left: 0.75rem; color: #8a9490; }
        .root-topbar-search input {
            min-width: 0;
            flex: 1;
            height: 100%;
            border: 0;
            padding: 0 0.7rem;
            outline: 0;
            color: var(--root-ink);
            font-size: 0.72rem;
        }

        .root-user-button {
            min-height: 40px;
            border: 0;
            padding: 0.25rem 0.4rem;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            background: transparent;
            color: var(--root-ink);
            text-align: left;
        }

        .root-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #dcf6ed;
            color: var(--root-green-dark);
            font-weight: 800;
        }

        .root-user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .root-user-copy strong, .root-user-copy span { display: block; }
        .root-user-copy strong { font-size: 0.68rem; }
        .root-user-copy span { color: var(--root-muted); font-size: 0.58rem; }

        .root-notification-count {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 17px;
            height: 17px;
            border: 2px solid #fff;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dc3545;
            color: #fff;
            font-size: 0.52rem;
            font-weight: 800;
        }

        .root-dropdown {
            min-width: 260px;
            border: 1px solid var(--root-border);
            border-radius: 8px;
            padding: 0.45rem;
            box-shadow: 0 14px 35px rgba(20, 29, 26, 0.14);
        }

        .root-dropdown-title { padding: 0.45rem 0.65rem; font-size: 0.72rem; font-weight: 800; }
        .root-dropdown-empty { padding: 0.8rem 0.65rem; color: var(--root-muted); font-size: 0.68rem; }
        .root-dropdown .dropdown-item { border-radius: 6px; padding: 0.55rem 0.65rem; font-size: 0.68rem; }
        .root-notification-item strong, .root-notification-item span { display: block; }
        .root-notification-item span { margin-top: 0.12rem; color: var(--root-muted); font-size: 0.58rem; }

        .root-page {
            width: 100%;
            max-width: 1540px;
            margin: 0 auto;
            padding: 1.35rem 1.5rem 2rem;
            flex: 1 0 auto;
        }

        .root-footer {
            min-height: 52px;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid var(--root-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            color: var(--root-muted);
            background: #fff;
            font-size: 0.62rem;
        }

        .root-sidebar-backdrop { display: none; }

        @media (max-width: 991.98px) {
            .root-sidebar { transform: translateX(-100%); transition: transform 0.22s ease; }
            .root-sidebar.open { transform: translateX(0); }
            .root-content { margin-left: 0; }
            .root-mobile-toggle { display: inline-flex; }
            .root-sidebar-backdrop {
                position: fixed;
                inset: 0;
                z-index: 1025;
                background: rgba(15,18,17,0.45);
            }
            .root-sidebar-backdrop.show { display: block; }
            .root-user-copy { display: none; }
        }

        @media (max-width: 575.98px) {
            .root-topbar { padding: 0 0.85rem; }
            .root-page { padding: 1rem 0.85rem 1.5rem; }
            .root-live, .root-breadcrumb span, .root-topbar-search { display: none; }
            .root-footer { align-items: flex-start; flex-direction: column; padding: 0.75rem 0.85rem; }
        }
    </style>
    @include('admin.partials.styles')
</head>
<body>
    <div class="root-shell">
        <aside class="root-sidebar" data-root-sidebar>
            <a href="{{ route('admin.super-admin') }}" class="root-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink" class="root-brand-mark">
                <span><span class="root-brand-name">Trung tâm Chill Drink</span><span class="root-brand-mode">Không gian quản trị cấp cao</span></span>
            </a>

            <div class="root-access">
                <span class="root-access-icon"><i class="bi bi-shield-lock-fill"></i></span>
                <div><strong>Quyền truy cập cấp cao</strong><span>Toàn quyền hệ thống</span></div>
            </div>

            <nav class="root-nav">
                <p class="root-nav-label">Điều hành</p>
                <a href="{{ route('admin.super-admin') }}" class="root-nav-link" data-root-section="top"><i class="bi bi-grid-1x2"></i> Tổng quan</a>
                <a href="{{ route('admin.super-admin') . '#admins' }}" class="root-nav-link" data-root-section="admins"><i class="bi bi-person-badge"></i> Quản trị viên <span class="root-nav-badge">{{ \App\Models\User::admins()->count() }}</span></a>
                <a href="{{ route('admin.super-admin') . '#branch-ranking' }}" class="root-nav-link" data-root-section="branch-ranking"><i class="bi bi-shop"></i> Chi nhánh <span class="root-nav-badge">{{ \App\Models\Branch::count() }}</span></a>
                <a href="{{ route('admin.super-admin') . '#permissions' }}" class="root-nav-link" data-root-section="permissions"><i class="bi bi-key"></i> Vai trò và phân quyền</a>
                <a href="{{ route('admin.chat.index') }}" class="root-nav-link {{ request()->routeIs('admin.chat.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots"></i> Chat khách hàng
                    @php
                        $unreadChatMessages = auth()->user()?->unreadConversationMessagesCount() ?? 0;
                    @endphp
                    @if($unreadChatMessages > 0)
                        <span class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.72rem;">{{ $unreadChatMessages > 99 ? '99+' : $unreadChatMessages }}</span>
                    @endif
                </a>

                <p class="root-nav-label">Quản lý cửa hàng</p>
                <a href="{{ route('admin.vouchers.index') }}" class="root-nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}"><i class="bi bi-ticket-perforated"></i> Phiếu ưu đãi</a>
                <a href="{{ route('admin.toppings.index') }}" class="root-nav-link {{ request()->routeIs('admin.toppings.*') ? 'active' : '' }}"><i class="bi bi-egg-fried"></i> Topping</a>
                <a href="{{ route('admin.products.index') }}" class="root-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i class="bi bi-cup-hot"></i> Sản phẩm</a>
                <a href="{{ route('admin.categories.index') }}" class="root-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-folder2"></i> Danh mục</a>
                <a href="{{ route('admin.slides.index') }}" class="root-nav-link {{ request()->routeIs('admin.slides.*') ? 'active' : '' }}"><i class="bi bi-images"></i> Trình chiếu</a>
                <a href="{{ route('admin.orders.index') }}" class="root-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Đơn hàng</a>
                <a href="{{ route('admin.group-orders.index') }}" class="root-nav-link {{ request()->routeIs('admin.group-orders.*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i> Đơn nhóm</a>
                <a href="{{ route('admin.reviews.index') }}" class="root-nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="bi bi-chat-square-text"></i> Đánh giá</a>
                <a href="{{ route('admin.users.index') }}" class="root-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Khách hàng</a>

                <p class="root-nav-label">Hệ thống</p>
                <a href="#security" class="root-nav-link" data-root-section="security"><i class="bi bi-shield-check"></i> Bảo mật</a>
                <a href="#health" class="root-nav-link" data-root-section="health"><i class="bi bi-activity"></i> Tình trạng hệ thống</a>
                <a href="#audit" class="root-nav-link" data-root-section="audit"><i class="bi bi-journal-text"></i> Nhật ký hệ thống</a>
            </nav>

            <div class="root-sidebar-footer">
                <a href="{{ route('home') }}" class="root-nav-link"><i class="bi bi-house-door"></i> Về trang chủ</a>
            </div>
        </aside>

        <div class="root-sidebar-backdrop" data-root-backdrop></div>

        <div class="root-content">
            <header class="root-topbar">
                <div class="root-topbar-left">
                    <button type="button" class="root-mobile-toggle" data-root-toggle aria-label="Mở menu"><i class="bi bi-list"></i></button>
                    <div class="root-breadcrumb"><span>Chill Drink / Hệ thống / </span><strong>@yield('page-title', 'Quản trị cấp cao')</strong></div>
                </div>
                <div class="root-topbar-right">
                    <form class="root-topbar-search" method="GET" action="{{ route('admin.super-admin') }}" role="search">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm tên hoặc email Admin" aria-label="Tìm quản trị viên">
                    </form>
                    <div class="dropdown">
                        <button type="button" class="root-topbar-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo">
                            <i class="bi bi-bell"></i>
                            @if(($unreadNotificationCount ?? 0) > 0)
                                <span class="root-notification-count">{{ min(99, $unreadNotificationCount) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end root-dropdown">
                            <div class="root-dropdown-title">Thông báo</div>
                            @forelse(($notifications ?? collect()) as $notification)
                                <div class="dropdown-item-text root-notification-item">
                                    <strong>{{ data_get($notification->data, 'title', 'Thông báo hệ thống') }}</strong>
                                    <span>{{ data_get($notification->data, 'message', $notification->created_at?->diffForHumans()) }}</span>
                                </div>
                            @empty
                                <div class="root-dropdown-empty">Chưa có thông báo mới.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="root-user-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản quản trị cấp cao">
                            <span class="root-user-avatar">
                                @if(Auth::user()->avatar)
                                    <img src="{{ str_starts_with(Auth::user()->avatar, 'http') ? Auth::user()->avatar : asset('storage/'.ltrim(Auth::user()->avatar, '/')) }}" alt="{{ Auth::user()->name }}">
                                @else
                                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                @endif
                            </span>
                            <span class="root-user-copy"><strong>{{ Auth::user()->name }}</strong><span>Quản trị cấp cao</span></span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end root-dropdown">
                            <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Thông tin cá nhân</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="root-page">
                @yield('content')
            </main>

            <footer class="root-footer">
                <span>© 2026 Chill Drink · Quản trị cấp cao</span>
                <span>Phiên bản 1.0.0 · Cập nhật {{ now()->format('d/m/Y') }}</span>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const rootSidebar = document.querySelector('[data-root-sidebar]');
        const rootBackdrop = document.querySelector('[data-root-backdrop]');
        const closeRootSidebar = () => {
            rootSidebar?.classList.remove('open');
            rootBackdrop?.classList.remove('show');
        };

        document.querySelector('[data-root-toggle]')?.addEventListener('click', () => {
            rootSidebar?.classList.toggle('open');
            rootBackdrop?.classList.toggle('show');
        });
        rootBackdrop?.addEventListener('click', closeRootSidebar);
        document.querySelectorAll('.root-nav-link').forEach((link) => link.addEventListener('click', closeRootSidebar));

        const updateRootNavigation = () => {
            const section = window.location.hash.replace('#', '') || 'top';
            document.querySelectorAll('[data-root-section]').forEach((link) => {
                link.classList.toggle('active', link.dataset.rootSection === section);
            });
        };

        window.addEventListener('hashchange', updateRootNavigation);
        updateRootNavigation();
    </script>
</body>
</html>
