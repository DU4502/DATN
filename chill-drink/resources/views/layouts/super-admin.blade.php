<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin - {{ config('app.name', 'Chill Drink') }}</title>

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

        .root-page {
            width: 100%;
            max-width: 1540px;
            margin: 0 auto;
            padding: 1.35rem 1.5rem 2rem;
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
        }

        @media (max-width: 575.98px) {
            .root-topbar { padding: 0 0.85rem; }
            .root-page { padding: 1rem 0.85rem 1.5rem; }
            .root-live, .root-breadcrumb span { display: none; }
        }
    </style>
</head>
<body>
    <div class="root-shell">
        <aside class="root-sidebar" data-root-sidebar>
            <a href="{{ route('admin.super-admin') }}" class="root-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink" class="root-brand-mark">
                <span><span class="root-brand-name">Chill Control</span><span class="root-brand-mode">Super Admin Workspace</span></span>
            </a>

            <div class="root-access">
                <span class="root-access-icon"><i class="bi bi-shield-lock-fill"></i></span>
                <div><strong>Quyền truy cập cấp cao</strong><span>Toàn quyền hệ thống</span></div>
            </div>

            <nav class="root-nav">
                <p class="root-nav-label">Điều hành</p>
                <a href="{{ route('admin.super-admin') }}" class="root-nav-link" data-root-section="top"><i class="bi bi-command"></i> Trung tâm kiểm soát</a>
                <a href="#admins" class="root-nav-link" data-root-section="admins"><i class="bi bi-person-badge"></i> Quản trị viên <span class="root-nav-badge">{{ \App\Models\User::admins()->count() }}</span></a>
                <a href="#permissions" class="root-nav-link" data-root-section="permissions"><i class="bi bi-key"></i> Vai trò và phân quyền</a>
                <a href="#audit" class="root-nav-link" data-root-section="audit"><i class="bi bi-journal-text"></i> Nhật ký hệ thống</a>

                <p class="root-nav-label">Nền tảng</p>
                <a href="#" class="root-nav-link"><i class="bi bi-diagram-3"></i> Tích hợp dịch vụ</a>
                <a href="#" class="root-nav-link"><i class="bi bi-database-check"></i> Sao lưu dữ liệu</a>
                <a href="#" class="root-nav-link"><i class="bi bi-sliders"></i> Cấu hình hệ thống</a>
            </nav>

            <div class="root-sidebar-footer">
                <a href="{{ route('admin.dashboard') }}" class="root-nav-link"><i class="bi bi-arrow-left-square"></i> Về Admin thường</a>
                <div class="root-session">
                    <span class="root-session-avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                    <div><strong>{{ Auth::user()->name }}</strong><span>Phiên Super Admin</span></div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="root-logout"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
                </form>
            </div>
        </aside>

        <div class="root-sidebar-backdrop" data-root-backdrop></div>

        <div class="root-content">
            <header class="root-topbar">
                <div class="root-topbar-left">
                    <button type="button" class="root-mobile-toggle" data-root-toggle aria-label="Mở menu"><i class="bi bi-list"></i></button>
                    <div class="root-breadcrumb"><span>Chill Drink / Hệ thống / </span><strong>@yield('page-title', 'Super Admin')</strong></div>
                </div>
                <div class="root-topbar-right">
                    <span class="root-live">Hệ thống trực tuyến</span>
                    <button type="button" class="root-topbar-btn" title="Tìm kiếm"><i class="bi bi-search"></i></button>
                    <button type="button" class="root-topbar-btn position-relative" title="Thông báo"><i class="bi bi-bell"></i><span class="position-absolute top-0 end-0 translate-middle p-1 bg-danger border border-light rounded-circle"></span></button>
                </div>
            </header>

            <main class="root-page">
                @yield('content')
            </main>
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
