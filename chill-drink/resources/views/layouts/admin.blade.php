<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin - {{ config('app.name', 'Chill Drink') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --font-ui: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --admin-primary: #006b5f;
            --admin-primary-soft: #b3ebe1;
            --admin-secondary: #316760;
            --admin-surface: #ffffff;
            --admin-bg: #f1fcfa;
            --admin-soft: #e5f0ee;
            --admin-soft-2: #ebf6f4;
            --admin-border: #bcc9c6;
            --admin-ink: #141d1c;
            --admin-muted: #6d7a77;
            --admin-danger: #ba1a1a;
            --admin-shadow: 0 4px 24px rgba(56, 168, 153, 0.08);
        }

        body {
            margin: 0;
            color: var(--admin-ink);
            background: var(--admin-bg);
            font-family: var(--font-ui);
            font-size: 16px;
            line-height: 1.55;
            letter-spacing: 0;
            -webkit-font-smoothing: antialiased;
            text-rendering: geometricPrecision;
        }

        body,
        button,
        input,
        select,
        textarea,
        table {
            font-family: var(--font-ui) !important;
            letter-spacing: 0 !important;
        }

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            color: var(--admin-ink);
            font-weight: 700;
            letter-spacing: 0 !important;
            line-height: 1.25;
        }

        h1, .h1 {
            font-size: 1.95rem;
        }

        h2, .h2 {
            font-size: 1.65rem;
        }

        h3, .h3,
        .h4 {
            font-size: 1.18rem;
        }

        p {
            line-height: 1.55;
            font-weight: 400;
        }

        small,
        .small {
            font-size: 0.86rem;
            line-height: 1.45;
            letter-spacing: 0 !important;
        }

        label,
        th,
        .badge {
            letter-spacing: 0 !important;
        }

        .admin-shell {
            min-height: 100vh;
            padding-left: 256px;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 50;
            width: 256px;
            background: var(--admin-surface);
            box-shadow: 4px 0 24px rgba(56, 168, 153, 0.08);
            display: flex;
            flex-direction: column;
            padding: 24px 12px;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 32px;
            color: var(--admin-primary);
            text-decoration: none;
        }

        .admin-logo-mark,
        .admin-avatar,
        .admin-icon-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: var(--admin-primary);
            font-weight: 800;
        }

        .admin-logo-mark {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(0, 107, 95, 0.18);
            font-size: 1.15rem;
        }

        .admin-logo-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .admin-logo-subtitle {
            margin: 0;
            color: var(--admin-muted);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .admin-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 2px 0;
            padding: 13px 16px;
            border-radius: 999px;
            color: #3d4947;
            font-weight: 700;
            font-size: 0.96rem;
            text-decoration: none;
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        .admin-sidebar .nav-link i {
            width: 22px;
            display: inline-flex;
            justify-content: center;
            font-size: 1.05rem;
        }

        .admin-sidebar .nav-link:hover {
            color: var(--admin-primary);
            background: var(--admin-soft);
        }

        .admin-sidebar .nav-link.active {
            color: #154f48;
            background: var(--admin-primary-soft);
            transform: scale(0.99);
        }

        .admin-sidebar-footer {
            margin-top: auto;
            padding: 16px 4px 0;
            border-top: 1px solid rgba(188, 201, 198, 0.35);
        }

        .admin-content {
            min-width: 0;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 24px;
            background: rgba(241, 252, 250, 0.82);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 20px rgba(56, 168, 153, 0.05);
        }

        .admin-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .admin-topbar-actions .btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.55rem 1.15rem;
            line-height: 1;
        }

        .admin-search {
            position: relative;
            width: min(420px, 34vw);
        }

        .admin-search input,
        .admin-filter,
        .admin-input {
            width: 100%;
            border: 1px solid rgba(0, 107, 95, 0.16);
            border-radius: 999px;
            background: var(--admin-soft-2);
            color: var(--admin-ink);
            font-weight: 500;
            font-size: 0.96rem;
            padding: 0.72rem 1rem;
        }

        .admin-filter:focus,
        .admin-input:focus,
        .admin-search input:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 0.22rem rgba(0, 107, 95, 0.12);
            outline: none;
            background: #ffffff;
        }

        .admin-search input {
            padding-left: 2.8rem;
        }

        .admin-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--admin-muted);
            font-weight: 800;
        }

        .admin-empty-state {
            border: 1px dashed rgba(0, 107, 95, 0.24);
            border-radius: 18px;
            background: var(--admin-soft-2);
            color: var(--admin-muted);
            padding: 2rem;
            text-align: center;
        }

        .admin-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #9ad1c8, var(--admin-primary));
            box-shadow: 0 12px 24px rgba(0, 107, 95, 0.16);
            overflow: hidden;
            flex: 0 0 auto;
        }

        .admin-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .admin-page {
            padding: 32px;
            max-width: 1400px;
        }

        .admin-card {
            border: 1px solid rgba(188, 201, 198, 0.55);
            border-radius: 16px;
            background: var(--admin-surface);
            box-shadow: var(--admin-shadow);
            overflow: hidden;
        }

        .admin-table-card,
        .admin-table-card .table-responsive {
            overflow: visible;
        }

        .admin-metric {
            padding: 24px;
            min-height: 156px;
            transition: transform 0.18s ease;
        }

        .admin-metric:hover {
            transform: translateY(-3px);
        }

        .admin-icon-dot {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--admin-primary-soft);
            color: var(--admin-secondary);
        }

        .admin-kicker {
            color: var(--admin-muted);
            font-size: 0.77rem;
            font-weight: 700;
            letter-spacing: 0 !important;
            text-transform: uppercase;
        }

        .admin-value {
            color: var(--admin-ink);
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .admin-table {
            margin: 0;
        }

        .admin-table thead th {
            background: var(--admin-soft-2);
            color: var(--admin-muted);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0 !important;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 1rem 1.35rem;
        }

        .admin-table tbody td {
            color: var(--admin-ink);
            font-size: 0.96rem;
            font-weight: 500;
            padding: 1rem 1.35rem;
            vertical-align: middle;
        }

        .admin-table tbody tr {
            transition: background-color 0.16s ease;
        }

        .admin-table tbody tr:hover {
            background: #f7fffd;
        }

        .admin-thumb {
            width: 58px;
            height: 58px;
            border-radius: 13px;
            border: 1px solid rgba(188, 201, 198, 0.65);
            background: var(--admin-soft);
        }

        .admin-thumb img,
        .admin-thumb .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain !important;
            object-position: center;
            padding: 0.28rem;
            background: #ffffff;
            box-sizing: border-box;
        }

        .admin-form-image-preview,
        .admin-review-thumb {
            width: 88px;
            height: 88px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid rgba(0, 107, 95, 0.16);
            border-radius: 16px;
            background: var(--admin-soft-2);
            color: var(--admin-muted);
            flex: 0 0 auto;
        }

        .admin-form-image-preview img,
        .admin-review-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain !important;
            object-position: center;
            padding: 0.28rem;
            background: #ffffff;
            box-sizing: border-box;
        }

        .admin-review-thumb {
            width: 54px;
            height: 54px;
            border-radius: 13px;
            color: var(--admin-primary);
        }

        .admin-period-tabs {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .admin-period-pill {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.55rem 1rem;
            border: 1px solid rgba(0, 107, 95, 0.26);
            border-radius: 999px;
            color: var(--admin-primary);
            background: #ffffff;
            font-weight: 700;
            white-space: nowrap;
        }

        .admin-period-pill.active {
            color: #ffffff;
            background: var(--admin-primary);
            border-color: var(--admin-primary);
        }

        .admin-period-card {
            height: 100%;
            padding: 20px;
            border: 1px solid rgba(188, 201, 198, 0.55);
            border-radius: 14px;
            background: #ffffff;
        }

        .admin-rating {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #8a5a00;
            background: #fff4d6;
            border-radius: 999px;
            padding: 0.42rem 0.75rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .admin-action {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            color: var(--admin-secondary);
            background: transparent;
        }

        .admin-action:hover {
            background: var(--admin-primary-soft);
        }

        .admin-dropdown-menu {
            min-width: 190px;
            padding: 0.45rem;
            border: 1px solid rgba(188, 201, 198, 0.65);
            border-radius: 14px;
            box-shadow: 0 18px 42px rgba(7, 52, 58, 0.12);
            z-index: 1085;
        }

        .admin-dropdown-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            min-height: 38px;
            border-radius: 10px;
            color: var(--admin-ink);
            font-weight: 700;
        }

        .admin-dropdown-menu .dropdown-item:hover {
            color: var(--admin-primary);
            background: var(--admin-soft-2);
        }

        .admin-dropdown-menu .dropdown-item.danger:hover {
            color: var(--admin-danger);
            background: #fff1ef;
        }

        .admin-review-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .admin-filter-pill {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 107, 95, 0.16);
            border-radius: 999px;
            padding: 0.5rem 0.95rem;
            color: var(--admin-muted);
            background: var(--admin-soft);
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .admin-filter-pill.active {
            color: #154f48;
            background: var(--admin-primary-soft);
            border-color: var(--admin-primary-soft);
        }

        .btn {
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.96rem;
            letter-spacing: 0 !important;
            min-height: 42px;
            padding-inline: 1.1rem;
        }

        .btn-primary {
            --bs-btn-bg: var(--admin-primary);
            --bs-btn-border-color: var(--admin-primary);
            --bs-btn-hover-bg: #005048;
            --bs-btn-hover-border-color: #005048;
        }

        .btn-outline-primary {
            --bs-btn-color: var(--admin-primary);
            --bs-btn-border-color: rgba(0, 107, 95, 0.35);
            --bs-btn-hover-bg: var(--admin-primary);
            --bs-btn-hover-border-color: var(--admin-primary);
        }

        .badge {
            border-radius: 999px;
            font-weight: 700;
            padding: 0.45rem 0.8rem;
        }

        .badge-soft-primary {
            color: #154f48;
            background: var(--admin-primary-soft);
        }

        .badge-soft-muted {
            color: #3d4947;
            background: var(--admin-soft);
        }

        .badge-soft-danger {
            color: #93000a;
            background: #ffdad6;
        }

        .text-primary {
            color: var(--admin-primary) !important;
        }

        @media (max-width: 991.98px) {
            .admin-shell {
                padding-left: 0;
            }

            .admin-sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .admin-sidebar .nav {
                flex-direction: row !important;
                overflow-x: auto;
                gap: 6px;
            }

            .admin-sidebar .nav-link {
                white-space: nowrap;
            }

            .admin-topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-search {
                width: 100%;
            }

            .admin-page {
                padding: 20px;
            }

            .admin-table-card .table-responsive {
                overflow-x: auto;
                overflow-y: visible;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="admin-logo">
                <span class="admin-logo-mark"><i class="bi bi-cup-straw"></i></span>
                <span>
                    <span class="admin-logo-title d-block">Chill Drink</span>
                    <span class="admin-logo-subtitle">Bảng quản trị</span>
                </span>
            </a>

            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Tổng quát</a>
                <a href="{{ route('admin.vouchers.index') }}" class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}"><i class="bi bi-ticket-perforated"></i> Voucher</a>
                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i class="bi bi-cup-hot"></i> Sản phẩm</a>
                <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-grid"></i> Danh mục</a>
                <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Đơn hàng</a>
                <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="bi bi-chat-square-heart"></i> Đánh giá</a>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Khách hàng</a>
            </nav>

            <div class="admin-sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary w-100">Đăng xuất</button>
                </form>
            </div>
        </aside>

        <div class="admin-content">
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h1 class="h4 fw-bold text-primary mb-0">@yield('page-title', 'Tổng quát')</h1>
                    <div class="admin-search">
                        <span class="admin-search-icon"><i class="bi bi-search"></i></span>
                        <input type="search" placeholder="@yield('search-placeholder', 'Tìm kiếm trong quản trị...')">
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <a href="{{ route('home') }}" class="btn btn-outline-primary btn-sm">Xem web</a>
                    <span class="text-secondary fw-semibold d-none d-lg-inline">{{ Auth::user()->name }}</span>
                    @php
                        $adminAvatar = Auth::user()->avatar;
                        $adminAvatarIsImage = $adminAvatar && ! str_starts_with($adminAvatar, 'preset-');
                        $adminAvatarUrl = $adminAvatarIsImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($adminAvatar) : null;
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
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    </script>
</body>
</html>
