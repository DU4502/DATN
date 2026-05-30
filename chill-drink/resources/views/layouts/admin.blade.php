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

    <style>
        /* ─── Admin Design Tokens ─── */
        :root {
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --a-primary: #0D9373;
            --a-primary-dark: #067A5F;
            --a-primary-light: #E6F7F2;
            --a-primary-glow: rgba(13, 147, 115, 0.1);
            --a-accent: #10B981;
            --a-surface: #FFFFFF;
            --a-bg: #F8FAFB;
            --a-bg-subtle: #F1F5F4;
            --a-ink: #111827;
            --a-ink-secondary: #374151;
            --a-muted: #6B7280;
            --a-subtle: #9CA3AF;
            --a-border: #E5E7EB;
            --a-border-light: #F3F4F6;
            --a-danger: #EF4444;
            --a-warning: #F59E0B;
            --a-success: #10B981;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 9999px;
            --shadow-xs: 0 1px 2px rgba(0,0,0,0.04);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.06), 0 2px 4px -2px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.06), 0 4px 6px -4px rgba(0,0,0,0.03);
        }

        /* ─── Base ─── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--a-ink);
            background: var(--a-bg);
            font-family: var(--font-sans);
            font-size: 14px;
            line-height: 1.6;
            letter-spacing: -0.011em;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        body, button, input, select, textarea, table {
            font-family: var(--font-sans) !important;
            letter-spacing: -0.011em !important;
        }

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            color: var(--a-ink);
            font-weight: 700;
            letter-spacing: -0.025em !important;
            line-height: 1.25;
        }

        h1, .h1 { font-size: 1.75rem; }
        h2, .h2 { font-size: 1.375rem; }
        h3, .h3, .h4 { font-size: 1.0625rem; }

        p { line-height: 1.6; font-weight: 400; color: var(--a-ink-secondary); }

        small, .small {
            font-size: 0.8125rem;
            line-height: 1.45;
            letter-spacing: -0.011em !important;
        }

        label, th, .badge { letter-spacing: -0.011em !important; }

        /* ─── Layout ─── */
        .admin-shell { min-height: 100vh; padding-left: 260px; }

        .admin-sidebar {
            position: fixed; inset: 0 auto 0 0;
            z-index: 50; width: 260px;
            background: var(--a-surface);
            border-right: 1px solid var(--a-border);
            display: flex; flex-direction: column;
            padding: 20px 12px;
        }

        /* ─── Logo ─── */
        .admin-logo {
            display: flex; align-items: center; gap: 10px;
            padding: 0 12px 24px;
            color: var(--a-ink); text-decoration: none;
        }

        .admin-logo-mark, .admin-avatar, .admin-icon-dot {
            display: inline-flex; align-items: center; justify-content: center;
        }

        .admin-logo-mark {
            width: 38px; height: 38px;
            border-radius: var(--radius-md);
            background: var(--a-surface);
            border: 1.5px solid var(--a-border);
            box-shadow: var(--shadow-sm);
            font-size: 1rem; font-weight: 800;
        }

        .admin-logo-title {
            margin: 0; font-size: 1.0625rem;
            font-weight: 800; line-height: 1.1;
            color: var(--a-ink);
        }

        .admin-logo-subtitle {
            margin: 0; color: var(--a-muted);
            font-size: 0.6875rem; font-weight: 500;
        }

        /* ─── Sidebar Nav ─── */
        .admin-sidebar .nav-link {
            display: flex; align-items: center; gap: 10px;
            margin: 1px 0;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            color: var(--a-muted);
            font-weight: 600; font-size: 0.8125rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            display: inline-flex; justify-content: center;
            font-size: 1rem;
        }

        .admin-sidebar .nav-link:hover {
            color: var(--a-ink);
            background: var(--a-bg-subtle);
        }

        .admin-sidebar .nav-link.active {
            color: var(--a-primary);
            background: var(--a-primary-light);
            font-weight: 700;
        }

        .admin-sidebar-footer {
            margin-top: auto;
            padding: 16px 4px 0;
            border-top: 1px solid var(--a-border-light);
        }

        /* ─── Content ─── */
        .admin-content { min-width: 0; }

        .admin-topbar {
            position: sticky; top: 0; z-index: 40;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px;
            padding: 12px 28px;
            background: rgba(248, 250, 251, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--a-border);
        }

        .admin-topbar-actions {
            display: flex; align-items: center;
            gap: 10px; flex: 0 0 auto; white-space: nowrap;
        }

        .admin-topbar-actions .btn {
            min-height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.4rem 0.9rem; line-height: 1;
        }

        /* ─── Search ─── */
        .admin-search { position: relative; width: min(380px, 32vw); }

        .admin-search input, .admin-filter, .admin-input {
            width: 100%;
            border: 1.5px solid var(--a-border);
            border-radius: var(--radius-sm);
            background: var(--a-surface);
            color: var(--a-ink);
            font-weight: 500; font-size: 0.8125rem;
            padding: 0.55rem 0.85rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .admin-filter:focus, .admin-input:focus, .admin-search input:focus {
            border-color: var(--a-primary);
            box-shadow: 0 0 0 3px var(--a-primary-glow);
            outline: none;
            background: var(--a-surface);
        }

        .admin-search input { padding-left: 2.5rem; }

        .admin-search-icon {
            position: absolute; left: 0.85rem; top: 50%;
            transform: translateY(-50%);
            color: var(--a-subtle); font-size: 0.85rem;
        }

        /* ─── Empty State ─── */
        .admin-empty-state {
            border: 1.5px dashed var(--a-border);
            border-radius: var(--radius-lg);
            background: var(--a-bg-subtle);
            color: var(--a-muted);
            padding: 2rem; text-align: center;
        }

        /* ─── Avatar ─── */
        .admin-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #A7F3D0, var(--a-primary));
            color: #fff; font-weight: 700; font-size: 0.75rem;
            overflow: hidden; flex: 0 0 auto;
        }

        .admin-avatar img {
            width: 100%; height: 100%;
            display: block; object-fit: cover;
        }

        /* ─── Page ─── */
        .admin-page { padding: 28px 28px 40px; max-width: 1400px; }

        /* ─── Cards ─── */
        .admin-card {
            border: 1px solid var(--a-border);
            border-radius: var(--radius-lg);
            background: var(--a-surface);
            box-shadow: var(--shadow-xs);
        }

        .admin-table-card {
            overflow: hidden;
        }

        .admin-table-card .table-responsive {
            overflow-x: auto;
            overflow-y: hidden;
        }

        /* ─── Metrics ─── */
        .admin-metric {
            padding: 20px; min-height: 140px;
            transition: all 0.2s ease;
        }

        .admin-metric:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .admin-icon-dot {
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            background: var(--a-primary-light);
            color: var(--a-primary);
            font-size: 1rem;
        }

        .admin-kicker {
            color: var(--a-muted);
            font-size: 0.6875rem; font-weight: 600;
            letter-spacing: 0.04em !important;
            text-transform: uppercase;
        }

        .admin-value {
            color: var(--a-ink);
            font-size: 1.5rem; font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.03em !important;
        }

        /* ─── Tables ─── */
        .admin-table { margin: 0; }

        .admin-table thead th {
            background: var(--a-bg-subtle);
            color: var(--a-muted);
            font-size: 0.6875rem; font-weight: 700;
            letter-spacing: 0.04em !important;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 0.75rem 1.1rem;
            border-bottom: 1px solid var(--a-border);
        }

        .admin-table tbody td {
            color: var(--a-ink);
            font-size: 0.8125rem; font-weight: 500;
            padding: 0.75rem 1.1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--a-border-light);
        }

        .admin-table tbody tr { transition: background-color 0.15s ease; }
        .admin-table tbody tr:hover { background: var(--a-bg-subtle); }

        .admin-products-table {
            min-width: 860px;
        }

        .admin-products-filters {
            min-width: 0;
        }

        .admin-stock-summary {
            flex: 0 0 auto;
        }

        /* ─── Thumbnails ─── */
        .admin-thumb {
            width: 48px; height: 48px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--a-border);
            background: var(--a-bg-subtle);
            overflow: hidden;
        }

        .admin-thumb img, .admin-thumb .product-image {
            width: 100%; height: 100%;
            object-fit: contain !important; object-position: center;
            padding: 0.2rem; background: #fff; box-sizing: border-box;
        }

        .admin-form-image-preview, .admin-review-thumb {
            width: 80px; height: 80px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            border: 1px solid var(--a-border);
            border-radius: var(--radius-md);
            background: var(--a-bg-subtle);
            color: var(--a-muted); flex: 0 0 auto;
        }

        .admin-form-image-preview img, .admin-review-thumb img {
            width: 100%; height: 100%;
            object-fit: contain !important; object-position: center;
            padding: 0.2rem; background: #fff; box-sizing: border-box;
        }

        .admin-review-thumb {
            width: 48px; height: 48px;
            border-radius: var(--radius-sm);
            color: var(--a-primary);
        }

        /* ─── Period Tabs ─── */
        .admin-period-tabs {
            display: flex; flex-wrap: nowrap; gap: 6px;
            overflow-x: auto; padding-bottom: 2px;
        }

        .admin-period-pill {
            min-height: 36px;
            display: inline-flex; align-items: center; gap: 6px;
            padding: 0.4rem 0.85rem;
            border: 1.5px solid var(--a-border);
            border-radius: var(--radius-full);
            color: var(--a-muted); background: var(--a-surface);
            font-weight: 600; font-size: 0.75rem;
            white-space: nowrap;
            transition: all 0.15s ease;
        }

        .admin-period-pill.active {
            color: #fff;
            background: var(--a-primary);
            border-color: var(--a-primary);
        }

        .admin-period-card {
            height: 100%; padding: 18px;
            border: 1px solid var(--a-border);
            border-radius: var(--radius-md);
            background: var(--a-surface);
        }

        /* ─── Rating ─── */
        .admin-rating {
            display: inline-flex; align-items: center; gap: 4px;
            color: #92400E; background: #FEF3C7;
            border-radius: var(--radius-full);
            padding: 0.3rem 0.65rem;
            font-weight: 700; font-size: 0.75rem;
            white-space: nowrap;
        }

        /* ─── Actions ─── */
        .admin-action {
            width: 34px; height: 34px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: var(--radius-sm);
            color: var(--a-muted); background: transparent;
            transition: all 0.15s ease;
        }

        .admin-action:hover {
            background: var(--a-primary-light);
            color: var(--a-primary);
        }

        /* Pagination */
        .admin-pagination-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--a-border);
            background: var(--a-bg-subtle);
        }

        .admin-pagination-footer p {
            flex: 1 1 180px;
        }

        .admin-pagination-footer nav {
            flex: 0 1 auto;
        }

        .admin-pagination-footer .pagination {
            align-items: center;
            flex-wrap: wrap;
            gap: 0.25rem;
            justify-content: flex-end;
            margin: 0;
        }

        .admin-pagination-footer .page-link {
            min-width: 36px;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--a-border);
            border-radius: var(--radius-sm) !important;
            color: var(--a-primary);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.45rem 0.75rem;
            box-shadow: none;
        }

        .admin-pagination-footer .page-item.active .page-link {
            background: var(--a-primary);
            border-color: var(--a-primary);
            color: #fff;
        }

        .admin-pagination-footer .page-item.disabled .page-link {
            color: var(--a-subtle);
            background: var(--a-surface);
        }

        .admin-pagination-footer svg {
            width: 1rem;
            height: 1rem;
        }

        /* ─── Dropdown ─── */
        .admin-dropdown-menu {
            min-width: 180px; padding: 0.3rem;
            border: 1px solid var(--a-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 1085;
        }

        .admin-dropdown-menu .dropdown-item {
            display: flex; align-items: center; gap: 0.5rem;
            min-height: 34px;
            border-radius: var(--radius-sm);
            color: var(--a-ink); font-weight: 600;
            font-size: 0.8125rem;
        }

        .admin-dropdown-menu .dropdown-item:hover {
            color: var(--a-primary); background: var(--a-primary-light);
        }

        .admin-dropdown-menu .dropdown-item.danger:hover {
            color: var(--a-danger); background: #FEF2F2;
        }

        /* ─── Review Filters ─── */
        .admin-review-filters { display: flex; flex-wrap: wrap; gap: 0.4rem; }

        .admin-filter-pill {
            min-height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1.5px solid var(--a-border);
            border-radius: var(--radius-full);
            padding: 0.35rem 0.75rem;
            color: var(--a-muted); background: var(--a-surface);
            font-size: 0.75rem; font-weight: 700;
            line-height: 1; white-space: nowrap;
        }

        .admin-filter-pill.active {
            color: var(--a-primary);
            background: var(--a-primary-light);
            border-color: var(--a-primary-light);
        }

        /* ─── Buttons ─── */
        .btn {
            border-radius: var(--radius-full);
            font-weight: 600; font-size: 0.8125rem;
            letter-spacing: -0.011em !important;
            min-height: 36px;
            padding-inline: 1rem;
            transition: all 0.15s ease;
        }

        .btn:active { transform: scale(0.97); }

        .btn-primary {
            --bs-btn-bg: var(--a-primary);
            --bs-btn-border-color: var(--a-primary);
            --bs-btn-hover-bg: var(--a-primary-dark);
            --bs-btn-hover-border-color: var(--a-primary-dark);
            box-shadow: 0 1px 3px rgba(13,147,115,0.2);
        }

        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(13,147,115,0.25);
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            --bs-btn-color: var(--a-primary);
            --bs-btn-border-color: var(--a-border);
            --bs-btn-hover-bg: var(--a-primary);
            --bs-btn-hover-border-color: var(--a-primary);
            --bs-btn-hover-color: #fff;
            background: var(--a-surface);
        }

        /* ─── Badges ─── */
        .badge {
            border-radius: var(--radius-full);
            font-weight: 600; font-size: 0.6875rem;
            padding: 0.3rem 0.65rem;
        }

        .badge-soft-primary {
            color: var(--a-primary-dark); background: var(--a-primary-light);
        }

        .badge-soft-muted {
            color: var(--a-ink-secondary); background: var(--a-bg-subtle);
        }

        .badge-soft-danger {
            color: #991B1B; background: #FEE2E2;
        }

        .text-primary { color: var(--a-primary) !important; }

        /* ─── Responsive ─── */
        @media (max-width: 991.98px) {
            .admin-shell { padding-left: 0; }
            .admin-sidebar {
                position: static; width: 100%; height: auto;
                border-right: 0; border-bottom: 1px solid var(--a-border);
                padding: 12px;
            }
            .admin-sidebar .nav {
                flex-direction: row !important;
                overflow-x: auto; gap: 4px;
            }
            .admin-sidebar .nav-link { white-space: nowrap; }
            .admin-topbar {
                flex-direction: column; align-items: stretch;
            }
            .admin-search { width: 100%; }
            .admin-page { padding: 20px; }
            .admin-table-card .table-responsive {
                overflow-x: auto; overflow-y: visible;
            }
            .admin-products-header {
                align-items: stretch !important;
            }
            .admin-stock-summary {
                align-self: flex-start;
            }
            .admin-pagination-footer {
                align-items: stretch;
                padding: 0.9rem;
            }
            .admin-pagination-footer nav,
            .admin-pagination-footer p {
                width: 100%;
                flex-basis: 100%;
            }
            .admin-pagination-footer .pagination {
                justify-content: flex-start;
            }
        }

        @media (max-width: 575.98px) {
            .admin-page { padding: 14px; }
            .admin-products-filters {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                padding-bottom: 0.25rem;
                scrollbar-width: thin;
            }
            .admin-products-filters .btn {
                flex: 0 0 auto;
            }
            .admin-stock-summary {
                width: 100%;
            }
            .admin-table thead th,
            .admin-table tbody td {
                padding: 0.65rem 0.8rem;
            }
            .admin-pagination-footer .page-link {
                min-width: 34px;
                min-height: 34px;
                padding-inline: 0.65rem;
            }
        }

        /* ─── Page Transition ─── */
        .admin-page { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="admin-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="admin-logo-mark" style="object-fit: contain; padding: 3px;">
                <span>
                    <span class="admin-logo-title d-block">Chill Drink</span>
                    <span class="admin-logo-subtitle">Quản trị hệ thống</span>
                </span>
            </a>

            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Tổng quát</a>
                <a href="{{ route('admin.vouchers.index') }}" class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}"><i class="bi bi-ticket-perforated"></i> Voucher</a>
                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i class="bi bi-cup-hot"></i> Sản phẩm</a>
                <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-folder2"></i> Danh mục</a>
                <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Đơn hàng</a>
                <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="bi bi-chat-square-text"></i> Đánh giá</a>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Khách hàng</a>
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
                    <div class="admin-search">
                        <span class="admin-search-icon"><i class="bi bi-search"></i></span>
                        <input type="search" placeholder="@yield('search-placeholder', 'Tìm kiếm...')">
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <span class="text-secondary fw-medium d-none d-lg-inline" style="font-size: 0.8125rem;">{{ Auth::user()->name }}</span>
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
