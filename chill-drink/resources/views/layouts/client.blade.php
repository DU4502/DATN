@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Chill Drink') }} - @yield('title', 'Đồ Uống Online')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --font-ui: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --drink-primary: #008b7a;
            --drink-primary-dark: #006f62;
            --drink-primary-soft: #edf9f6;
            --drink-accent: #b8eadf;
            --drink-coral: #7fd8c7;
            --drink-ink: #071b19;
            --drink-muted: #647b78;
            --drink-soft: #eefaf7;
            --drink-border: #d5eee8;
            --drink-shadow: 0 18px 45px rgba(79, 183, 168, 0.15);
        }

        body {
            font-family: var(--font-ui);
            color: var(--drink-ink);
            background:
                radial-gradient(circle at top left, rgba(184, 234, 223, 0.38), transparent 34rem),
                linear-gradient(180deg, #f4fffc 0%, #edf9f6 100%);
        }

        body,
        button,
        input,
        select,
        textarea,
        table,
        .btn,
        .form-control,
        .form-select,
        .dropdown-menu {
            font-family: var(--font-ui) !important;
            letter-spacing: 0 !important;
        }

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: var(--font-ui);
            letter-spacing: 0 !important;
            line-height: 1.18;
        }

        .btn {
            border-radius: 999px;
            font-weight: 700;
            padding-inline: 1.1rem;
            min-height: 42px;
        }

        .btn-primary {
            --bs-btn-bg: var(--drink-primary);
            --bs-btn-border-color: var(--drink-primary);
            --bs-btn-hover-bg: var(--drink-primary-dark);
            --bs-btn-hover-border-color: var(--drink-primary-dark);
            --bs-btn-active-bg: var(--drink-primary-dark);
            --bs-btn-active-border-color: var(--drink-primary-dark);
            box-shadow: 0 10px 24px rgba(79, 183, 168, 0.20);
        }

        .btn-outline-primary {
            --bs-btn-color: var(--drink-primary);
            --bs-btn-border-color: rgba(79, 183, 168, 0.45);
            --bs-btn-hover-bg: var(--drink-primary);
            --bs-btn-hover-border-color: var(--drink-primary);
        }

        .text-primary {
            color: var(--drink-primary) !important;
        }

        .bg-primary {
            background-color: var(--drink-primary) !important;
        }

        .form-control,
        .form-select {
            border-color: var(--drink-border);
            border-radius: 999px;
            padding: 0.72rem 1rem;
            color: var(--drink-ink);
            font-weight: 500;
        }

        .form-label {
            color: var(--drink-ink);
            font-weight: 700;
        }

        textarea.form-control {
            border-radius: 18px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--drink-primary);
            box-shadow: 0 0 0 0.22rem rgba(79, 183, 168, 0.16);
        }

        .card,
        .dropdown-menu,
        .list-group-item {
            border-color: var(--drink-border) !important;
        }

        .list-group-item {
            color: var(--drink-ink);
            padding: 0.9rem 1.1rem;
        }

        .list-group-item:hover {
            color: var(--drink-primary);
            background: var(--drink-soft);
        }

        .list-group-item.active {
            color: #ffffff;
            background: linear-gradient(135deg, var(--drink-primary), var(--drink-primary-dark));
            border-color: var(--drink-primary) !important;
            box-shadow: 0 10px 22px rgba(15, 139, 141, 0.22);
        }

        .drink-card {
            border: 1px solid var(--drink-border);
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(18, 56, 63, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .drink-card:hover {
            transform: translateY(-5px);
            border-color: rgba(79, 183, 168, 0.35);
            box-shadow: var(--drink-shadow);
        }

        .section-kicker {
            color: var(--drink-primary);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .section-title {
            color: var(--drink-ink);
            font-weight: 800;
            letter-spacing: 0;
        }

        .site-header {
            background: rgba(239, 252, 248, 0.94);
            border-bottom: 1px solid rgba(213, 238, 232, 0.95);
            backdrop-filter: blur(18px);
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: var(--drink-primary);
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 12px 28px rgba(0, 107, 95, 0.18);
            font-size: 1.15rem;
        }

        .brand-text {
            color: var(--drink-ink);
            letter-spacing: 0;
            font-size: 1.25rem;
        }

        .nav-link {
            border-radius: 999px;
            font-weight: 700;
            padding: 0.65rem 1rem !important;
        }

        .nav-link:hover {
            color: var(--drink-primary) !important;
            background: rgba(79, 183, 168, 0.10);
        }

        .nav-link.active,
        .nav-link.fw-semibold {
            color: var(--drink-primary) !important;
            background: rgba(79, 183, 168, 0.14);
        }

        .client-search {
            width: clamp(220px, 24vw, 300px);
        }

        /* Tailwind/Bootstrap conflict: luôn hiện menu trên màn hình >= md */
        @media (min-width: 768px) {
            #clientNavbar.navbar-collapse {
                display: flex !important;
                flex-basis: auto;
                flex-grow: 1;
                align-items: center;
                visibility: visible !important;
            }
        }

        @media (max-width: 767.98px) {
            #clientNavbar.navbar-collapse:not(.show) {
                display: none !important;
            }

            #clientNavbar.navbar-collapse.show {
                display: flex !important;
                flex-direction: column;
                align-items: stretch;
                visibility: visible !important;
            }
        }

        .navbar-toggler {
            border-color: var(--drink-border);
            border-radius: 999px;
            padding: 0.55rem 0.75rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.22rem rgba(79, 183, 168, 0.16);
        }

        .cart-button {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ffffff;
            border-color: var(--drink-border);
            color: var(--drink-primary);
            padding: 0;
        }

        .cart-button svg {
            width: 21px;
            height: 21px;
            display: block;
            flex: 0 0 auto;
        }

        .cart-bump {
            animation: cartBump 0.55s ease;
            box-shadow: 0 14px 32px rgba(0, 139, 122, 0.24);
        }

        .cart-button [data-cart-badge] {
            transition: transform 0.18s ease;
        }

        .cart-bump [data-cart-badge] {
            transform: scale(1.12);
        }

        .cart-fly-dot {
            position: fixed;
            z-index: 1080;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--drink-primary);
            color: #ffffff;
            box-shadow: 0 16px 34px rgba(0, 139, 122, 0.28);
            pointer-events: none;
            transform: translate(-50%, -50%) scale(1);
            transition: transform 0.72s cubic-bezier(.22, .88, .22, 1), opacity 0.72s ease;
        }

        .cart-feedback {
            position: fixed;
            right: 1.25rem;
            top: 5.4rem;
            z-index: 1081;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            max-width: min(340px, calc(100vw - 2rem));
            padding: 0.8rem 1rem;
            border: 1px solid rgba(0, 139, 122, 0.15);
            border-radius: 999px;
            background: #ffffff;
            color: var(--drink-ink);
            box-shadow: 0 20px 46px rgba(8, 42, 38, 0.16);
            font-weight: 700;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.22s ease, transform 0.22s ease;
            pointer-events: none;
        }

        .cart-feedback.show {
            opacity: 1;
            transform: translateY(0);
        }

        .cart-feedback-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--drink-soft);
            color: var(--drink-primary);
            flex: 0 0 auto;
        }

        [data-ajax-cart].is-adding button[type="submit"],
        [data-ajax-cart].is-added button[type="submit"] {
            transform: translateY(-1px);
        }

        @keyframes cartBump {
            0% { transform: scale(1); }
            35% { transform: scale(1.12); }
            70% { transform: scale(0.96); }
            100% { transform: scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .cart-button.cart-bump,
            .cart-fly-dot,
            .cart-feedback {
                animation: none;
                transition: none;
            }
        }

        .user-avatar {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, #9fe2d5, var(--drink-primary));
            color: #ffffff;
            font-weight: 800;
            font-size: 1.2rem;
            box-shadow: 0 14px 32px rgba(79, 183, 168, 0.28);
            padding: 0;
            overflow: hidden;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .user-avatar.show {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(79, 183, 168, 0.34);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-preset-mint {
            background: linear-gradient(135deg, #9fe2d5, #008b7a);
        }

        .avatar-preset-sky {
            background: linear-gradient(135deg, #9ddcff, #1d8bd6);
        }

        .avatar-preset-berry {
            background: linear-gradient(135deg, #f6a6c8, #b83280);
        }

        .avatar-preset-orange {
            background: linear-gradient(135deg, #ffd08a, #e97828);
        }

        .user-avatar::after {
            display: none;
        }

        .notification-button {
            position: relative;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--drink-border);
            border-radius: 50%;
            background: #ffffff;
            color: var(--drink-primary);
            box-shadow: 0 12px 26px rgba(8, 42, 38, 0.08);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .notification-button:hover,
        .notification-button.show {
            border-color: rgba(0, 139, 122, 0.35);
            color: var(--drink-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(0, 107, 95, 0.14);
        }

        .notification-button::after {
            display: none;
        }

        .notification-dot {
            position: absolute;
            top: 5px;
            right: 6px;
            width: 10px;
            height: 10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            background: #f26a4f;
        }

        .notification-menu {
            width: min(360px, calc(100vw - 2rem));
            margin-top: 0.6rem !important;
            padding: 0;
            border: 1px solid var(--drink-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 22px 52px rgba(8, 42, 38, 0.16);
        }

        .notification-head {
            padding: 1rem 1.1rem;
            background: linear-gradient(135deg, var(--drink-primary-soft), #ffffff);
            border-bottom: 1px solid var(--drink-border);
        }

        .notification-list {
            max-height: 330px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            gap: 0.85rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid rgba(213, 238, 232, 0.65);
            background: #ffffff;
        }

        .notification-item:last-child {
            border-bottom: 0;
        }

        .notification-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 12px;
            background: var(--drink-primary-soft);
            color: var(--drink-primary);
        }

        .notification-time {
            color: var(--drink-muted);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .profile-menu {
            min-width: 200px;
            margin-top: 0.45rem !important;
            padding: 0.55rem 0;
            border: 1px solid var(--drink-border);
            border-radius: 10px;
            box-shadow: 0 18px 44px rgba(8, 42, 38, 0.14);
        }

        .dropdown:hover > .profile-menu:not(.show) {
            display: none !important;
        }

        .profile-menu.show {
            display: block;
        }

        .profile-menu .dropdown-item {
            color: #2f3b3a;
            font-size: 1.05rem;
            font-weight: 500;
            padding: 0.72rem 1.15rem;
            border-radius: 8px;
            transition: color 0.16s ease, background-color 0.16s ease;
        }

        .profile-menu .dropdown-item:hover,
        .profile-menu .dropdown-item:focus {
            color: var(--drink-primary);
            background: var(--drink-primary-soft);
        }

        .profile-menu form {
            margin: 0;
        }

        .profile-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
        }

        .profile-tab {
            border: 1px solid var(--drink-border);
            border-radius: 999px;
            padding: 0.65rem 1.15rem;
            font-weight: 700;
            color: var(--drink-muted);
            text-decoration: none;
            background: #ffffff;
            transition: border-color 0.18s ease, color 0.18s ease, background 0.18s ease;
        }

        .profile-tab:hover,
        .profile-tab.active {
            border-color: var(--drink-primary);
            color: var(--drink-primary-dark);
            background: var(--drink-primary-soft);
        }

        .site-footer {
            position: relative;
            overflow: hidden;
            color: var(--drink-ink) !important;
            background: #f8fffd;
            border-top: 1px solid var(--drink-border);
            border-top-left-radius: 22px;
            border-top-right-radius: 22px;
            box-shadow: 0 -10px 28px rgba(0, 107, 95, 0.04);
        }

        .site-footer .container {
            position: relative;
            z-index: 1;
        }

        .footer-link {
            color: var(--drink-muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.18s ease, transform 0.18s ease;
        }

        .footer-link:hover {
            color: var(--drink-primary);
            transform: translateX(3px);
        }

        .nav-actions .btn-outline-primary,
        .nav-actions .btn-primary {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 991.98px) {
            .client-search {
                width: 100%;
                margin-top: 0.75rem;
            }

            .navbar-collapse {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--drink-border);
            }

            .nav-actions {
                width: 100%;
                align-items: stretch !important;
            }

            .nav-actions .btn:not(.cart-button),
            .nav-actions .dropdown {
                width: 100%;
            }

            .notification-button {
                margin-inline: auto;
            }

            .nav-actions .dropdown .user-avatar {
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>
<body class="bg-light">
    <header class="site-header sticky-top">
        <nav class="navbar navbar-expand-md container py-3">
            <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-2 fw-bold m-0">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="brand-mark" style="background: white; object-fit: contain; padding: 2px;">
                <span class="brand-text">Chill Drink</span>
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#clientNavbar" aria-controls="clientNavbar" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse flex-grow-1" id="clientNavbar">
                <ul class="navbar-nav ms-lg-4 gap-lg-1">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : 'text-dark' }}">Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : 'text-dark' }}">Sản Phẩm</a>
                    </li>
                </ul>

                <div class="nav-actions d-flex flex-wrap align-items-center gap-2 ms-lg-auto mt-3 mt-lg-0">
                    <form action="{{ route('products.index') }}" method="GET" class="d-flex client-search" role="search">
                        <input
                            type="search"
                            name="search"
                            class="form-control"
                            placeholder="Tìm kiếm đồ uống..."
                            aria-label="Tìm kiếm sản phẩm"
                            value="{{ request('search') }}"
                        >
                        <button type="submit" class="btn btn-primary">Tìm</button>
                    </form>

                    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary cart-button position-relative" aria-label="Giỏ hàng" data-cart-button>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 8.25h10.5l-.75 10.5a2.25 2.25 0 0 1-2.25 2.1h-6.5a2.25 2.25 0 0 1-2.25-2.1L4.75 8.25Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 8.25a3.25 3.25 0 0 1 6.5 0" />
                        </svg>
                        <span data-cart-badge class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ session('cart') ? '' : 'd-none' }}">
                            {{ session('cart') ? count(session('cart')) : 0 }}
                        </span>
                    </a>

                    @auth
                        @php
                            $avatar = Auth::user()->avatar;
                            $avatarIsPreset = is_string($avatar) && str_starts_with($avatar, 'preset-');
                            $avatarClass = $avatarIsPreset ? 'avatar-' . $avatar : 'avatar-preset-mint';
                            $avatarUrl = $avatar && ! $avatarIsPreset ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatar) : null;
                        @endphp
                        <div class="dropdown">
                            <button class="notification-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo đơn hàng">
                                <i class="bi bi-bell"></i>
                                <span class="notification-dot" aria-hidden="true"></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-menu">
                                <div class="notification-head">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-bold">Thông báo đơn hàng</div>
                                            <div class="text-secondary small">Cập nhật khi đơn chuẩn bị giao tới bạn.</div>
                                        </div>
                                        <span class="badge rounded-pill text-bg-success">Mới</span>
                                    </div>
                                </div>
                                <div class="notification-list">
                                    <div class="notification-item">
                                        <span class="notification-icon"><i class="bi bi-truck"></i></span>
                                        <div>
                                            <div class="fw-bold">Shipper sắp đến</div>
                                            <div class="text-secondary small">Đơn hàng đang ở gần địa chỉ nhận. Bạn chuẩn bị nhận đồ uống nhé.</div>
                                            <div class="notification-time mt-1">Vừa xong</div>
                                        </div>
                                    </div>
                                    <div class="notification-item">
                                        <span class="notification-icon"><i class="bi bi-cup-straw"></i></span>
                                        <div>
                                            <div class="fw-bold">Đơn đang được giao</div>
                                            <div class="text-secondary small">Đồ uống đã rời cửa hàng và đang trên đường tới bạn.</div>
                                            <div class="notification-time mt-1">10 phút trước</div>
                                        </div>
                                    </div>
                                    <div class="notification-item">
                                        <span class="notification-icon"><i class="bi bi-check2-circle"></i></span>
                                        <div>
                                            <div class="fw-bold">Giao hàng thành công</div>
                                            <div class="text-secondary small">Cảm ơn bạn đã đặt hàng tại Chill Drink.</div>
                                            <div class="notification-time mt-1">Hôm nay</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 border-top">
                                    <a href="{{ route('profile.orders') }}" class="btn btn-primary w-100">Xem đơn hàng của tôi</a>
                                </div>
                            </div>
                        </div>

                        <div class="dropdown text-center">
                            <button class="user-avatar dropdown-toggle {{ $avatarClass }}" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ Auth::user()->name }}">
                                @else
                                    {{ mb_substr(Auth::user()->name, 0, 1) }}
                                @endif
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end profile-menu">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Tài khoản</a></li>
                                <li><a class="dropdown-item" href="{{ route('profile.orders') }}">Đơn hàng của tôi</a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Đăng Xuất</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary {{ request()->routeIs('login') ? 'active' : '' }}">Đăng Nhập</a>
                        <a href="{{ route('register') }}" class="btn btn-primary {{ request()->routeIs('register') ? 'active' : '' }}">Đăng Ký</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main style="min-height: 100vh;">
        @if(session('error'))
            <div class="container mt-4">
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer mt-5">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="brand-mark" style="background: white; object-fit: contain; padding: 2px;">
                        <h3 class="h5 fw-bold mb-0">Chill Drink</h3>
                    </div>
                    <p class="text-secondary mb-3">Đồ uống tươi mát, giao nhanh và đặt hàng dễ dàng mỗi ngày.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="footer-link">◎</a>
                        <a href="#" class="footer-link">@</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h3 class="h6 fw-bold text-uppercase mb-3">Liên Hệ</h3>
                    <ul class="list-unstyled text-secondary mb-0">
                        <li class="mb-2">Hotline: 1900-xxxx</li>
                        <li class="mb-2">Email: contact@chilldrink.com</li>
                        <li>Địa chỉ: Hà Nội, Việt Nam</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h3 class="h6 fw-bold text-uppercase mb-3">Mạng Xã Hội</h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="#" class="footer-link">Facebook</a>
                        <a href="#" class="footer-link">Instagram</a>
                        <a href="#" class="footer-link">Zalo</a>
                    </div>
                </div>
            </div>
            <div class="border-top mt-4 pt-4 text-center text-secondary">
                <p class="mb-0">&copy; 2026 Chill Drink. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.bootstrap) {
                return;
            }

            const navbarToggler = document.querySelector('[data-bs-target="#clientNavbar"]');
            const clientNavbar = document.getElementById('clientNavbar');

            navbarToggler?.addEventListener('click', function () {
                const isOpen = clientNavbar?.classList.toggle('show');
                navbarToggler.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const menu = button.parentElement?.querySelector('.dropdown-menu');
                    const willOpen = !menu?.classList.contains('show');

                    document.querySelectorAll('.dropdown-menu.show').forEach(function (openMenu) {
                        openMenu.classList.remove('show');
                        openMenu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]')?.classList.remove('show');
                    });

                    if (menu && willOpen) {
                        menu.classList.add('show');
                        button.classList.add('show');
                        button.setAttribute('aria-expanded', 'true');
                    } else {
                        button.classList.remove('show');
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
                    menu.classList.remove('show');
                    const button = menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
                    button?.classList.remove('show');
                    button?.setAttribute('aria-expanded', 'false');
                });
            });
        });

        document.addEventListener('submit', async function (event) {
            const form = event.target;

            if (!form.matches('[data-ajax-cart]')) {
                return;
            }

            event.preventDefault();

            const submitter = event.submitter;
            const formData = new FormData(form);
            const isAddAction = form.action.includes('/cart/add/');
            const originalSubmitterHtml = submitter?.innerHTML;

            function cartButton() {
                const buttons = Array.from(document.querySelectorAll('[data-cart-button]'));
                const visibleButtons = buttons.filter((button) => {
                    const rect = button.getBoundingClientRect();
                    return rect.width > 0 && rect.height > 0;
                });

                return visibleButtons.at(-1) || document.querySelector('.cart-button');
            }

            function setAddButtonState(state) {
                if (!isAddAction || !submitter) {
                    return;
                }

                form.classList.toggle('is-adding', state === 'loading');
                form.classList.toggle('is-added', state === 'success');
                submitter.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');

                const hasText = submitter.textContent.trim().length > 0;

                if (state === 'loading' && hasText) {
                    submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang thêm';
                }

                if (state === 'success' && hasText) {
                    submitter.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Đã thêm';
                }

                if (state === 'idle' && typeof originalSubmitterHtml === 'string') {
                    submitter.innerHTML = originalSubmitterHtml;
                    submitter.removeAttribute('aria-busy');
                    form.classList.remove('is-adding', 'is-added');
                }
            }

            function animateCartButton() {
                const target = cartButton();

                if (!target) {
                    return;
                }

                target.classList.remove('cart-bump');
                void target.offsetWidth;
                target.classList.add('cart-bump');
                window.setTimeout(() => target.classList.remove('cart-bump'), 620);
            }

            function flyToCart() {
                const target = cartButton();
                const source = submitter || form;

                if (!isAddAction || !target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    animateCartButton();
                    return;
                }

                const sourceRect = source.getBoundingClientRect();
                const targetRect = target.getBoundingClientRect();
                const dot = document.createElement('span');

                dot.className = 'cart-fly-dot';
                dot.innerHTML = '<i class="bi bi-cup-straw" aria-hidden="true"></i>';
                dot.style.left = `${sourceRect.left + sourceRect.width / 2}px`;
                dot.style.top = `${sourceRect.top + sourceRect.height / 2}px`;
                document.body.appendChild(dot);

                requestAnimationFrame(() => {
                    dot.style.transform = `translate(${targetRect.left + targetRect.width / 2 - (sourceRect.left + sourceRect.width / 2)}px, ${targetRect.top + targetRect.height / 2 - (sourceRect.top + sourceRect.height / 2)}px) scale(0.35)`;
                    dot.style.opacity = '0.15';
                });

                window.setTimeout(() => {
                    dot.remove();
                    animateCartButton();
                }, 760);
            }

            function showCartFeedback(message) {
                if (!isAddAction) {
                    return;
                }

                let feedback = document.querySelector('[data-cart-feedback]');

                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'cart-feedback';
                    feedback.dataset.cartFeedback = 'true';
                    feedback.setAttribute('role', 'status');
                    feedback.setAttribute('aria-live', 'polite');
                    document.body.appendChild(feedback);
                }

                feedback.innerHTML = `
                    <span class="cart-feedback-icon"><i class="bi bi-bag-check"></i></span>
                    <span>${message || 'Đã thêm vào giỏ hàng'}</span>
                `;
                feedback.classList.add('show');

                window.clearTimeout(feedback._hideTimer);
                feedback._hideTimer = window.setTimeout(() => {
                    feedback.classList.remove('show');
                }, 1800);
            }

            if (submitter && submitter.name) {
                formData.set(submitter.name, submitter.value);
            }

            if (submitter) {
                submitter.disabled = true;
            }

            setAddButtonState('loading');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const badges = document.querySelectorAll('[data-cart-badge]');

                badges.forEach((badge) => {
                    badge.textContent = data.count;
                    badge.classList.toggle('d-none', data.count < 1);
                });

                if (isAddAction) {
                    setAddButtonState('success');
                    flyToCart();
                    showCartFeedback(data.message);
                }

                if (document.body.dataset.page === 'cart') {
                    if (data.count < 1) {
                        const cartContainer = document.querySelector('.cart-page .container');

                        if (cartContainer) {
                            cartContainer.innerHTML = `
                                <div class="mb-5">
                                    <p class="section-kicker mb-2">Giỏ hàng</p>
                                    <h1 class="cart-title mb-0">Giỏ hàng của bạn</h1>
                                </div>
                                <div class="cart-summary-card text-center p-5">
                                    <span class="checkout-step mx-auto mb-3"><i class="bi bi-bag"></i></span>
                                    <h2 class="h3 fw-bold">Giỏ hàng trống</h2>
                                    <p class="text-secondary">Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
                                    <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Mua sắm ngay</a>
                                </div>
                            `;
                        }

                        return;
                    }

                    Object.entries(data.items).forEach(([id, item]) => {
                        document.querySelectorAll(`[data-cart-quantity="${CSS.escape(id)}"]`).forEach((input) => {
                            input.value = item.quantity;

                            const qtyForm = input.closest('form');
                            const minusButton = qtyForm?.querySelector('button[aria-label^="Giảm"]');
                            const plusButton = qtyForm?.querySelector('button[aria-label^="Tăng"]');

                            if (minusButton) {
                                minusButton.value = Math.max(1, item.quantity - 1);
                            }

                            if (plusButton) {
                                plusButton.value = item.quantity + 1;
                            }
                        });

                        document.querySelectorAll(`[data-cart-subtotal="${CSS.escape(id)}"]`).forEach((element) => {
                            element.textContent = item.subtotal_formatted;
                        });

                        document.querySelectorAll(`[data-cart-row][data-cart-key="${CSS.escape(id)}"]`).forEach((row) => {
                            row.dataset.cartSubtotalValue = item.subtotal;
                        });
                    });

                    document.querySelectorAll('[data-cart-total]').forEach((element) => {
                        element.textContent = data.total_formatted;
                    });

                    if (form.dataset.cartRemove === 'true') {
                        form.closest('[data-cart-row]')?.remove();
                    }

                    document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
                }
            } catch (error) {
                console.error('Không thể cập nhật giỏ hàng.', error);
            } finally {
                if (submitter) {
                    window.setTimeout(() => {
                        submitter.disabled = false;
                        setAddButtonState('idle');
                    }, isAddAction ? 900 : 0);
                }
            }
        });
    </script>
</body>
</html>
