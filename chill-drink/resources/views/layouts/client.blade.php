<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Chill Drink') }} - @yield('title', 'Đồ Uống Online')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --drink-primary: #0f8b8d;
            --drink-primary-dark: #086972;
            --drink-accent: #ffb703;
            --drink-coral: #fb6f5b;
            --drink-ink: #172033;
            --drink-muted: #65758b;
            --drink-soft: #f3fbf8;
            --drink-border: #dcebe8;
            --drink-shadow: 0 18px 45px rgba(18, 56, 63, 0.12);
        }

        body {
            color: var(--drink-ink);
            background:
                radial-gradient(circle at top left, rgba(15, 139, 141, 0.12), transparent 34rem),
                linear-gradient(180deg, #fbfffd 0%, #f4f8fb 100%);
        }

        .btn {
            border-radius: 999px;
            font-weight: 700;
            padding-inline: 1.1rem;
        }

        .btn-primary {
            --bs-btn-bg: var(--drink-primary);
            --bs-btn-border-color: var(--drink-primary);
            --bs-btn-hover-bg: var(--drink-primary-dark);
            --bs-btn-hover-border-color: var(--drink-primary-dark);
            --bs-btn-active-bg: var(--drink-primary-dark);
            --bs-btn-active-border-color: var(--drink-primary-dark);
            box-shadow: 0 10px 24px rgba(15, 139, 141, 0.22);
        }

        .btn-outline-primary {
            --bs-btn-color: var(--drink-primary);
            --bs-btn-border-color: rgba(15, 139, 141, 0.35);
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
        }

        textarea.form-control {
            border-radius: 18px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--drink-primary);
            box-shadow: 0 0 0 0.22rem rgba(15, 139, 141, 0.13);
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
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(18, 56, 63, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .drink-card:hover {
            transform: translateY(-5px);
            border-color: rgba(15, 139, 141, 0.28);
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

        .client-navbar {
            gap: 24px;
        }

        .site-header {
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid rgba(220, 235, 232, 0.9);
            backdrop-filter: blur(18px);
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--drink-primary), #3bd6b5);
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 12px 28px rgba(15, 139, 141, 0.24);
        }

        .brand-text {
            color: var(--drink-ink);
            letter-spacing: 0;
        }

        .nav-link {
            border-radius: 999px;
            font-weight: 700;
        }

        .nav-link:hover {
            color: var(--drink-primary) !important;
            background: rgba(15, 139, 141, 0.08);
        }

        .nav-link.active,
        .nav-link.fw-semibold {
            color: var(--drink-primary) !important;
            background: rgba(15, 139, 141, 0.11);
        }

        .client-search {
            width: min(420px, 34vw);
        }

        .cart-button {
            width: 42px;
            height: 42px;
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

        .site-footer {
            background:
                radial-gradient(circle at 18% 20%, rgba(59, 214, 181, 0.18), transparent 24rem),
                linear-gradient(135deg, #101928, #0b363b 72%, #083a3f);
        }

        @media (max-width: 991.98px) {
            .client-navbar {
                gap: 12px;
            }

            .client-search {
                order: 4;
                width: min(420px, 100%);
                margin-left: auto;
            }
        }

        @media (max-width: 767.98px) {
            .client-navbar .nav {
                order: 3;
                width: 100%;
                justify-content: center;
            }

            .client-navbar > .d-flex.ms-auto {
                width: 100%;
                justify-content: center;
            }

            .client-search {
                width: 100%;
                max-width: 520px;
            }
        }

        @media (max-width: 575.98px) {
            .client-navbar {
                justify-content: center;
            }

            .brand-text {
                font-size: 1rem;
            }

            .client-search {
                order: 2;
            }
        }
    </style>
</head>
<body class="bg-light" style="font-family: Figtree, Arial, sans-serif;">
    <header class="site-header sticky-top">
        <nav class="container d-flex flex-wrap align-items-center py-3 client-navbar">
            <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-2 fw-bold m-0">
                <span class="brand-mark">C</span>
                <span class="brand-text">Chill Drink</span>
            </a>

            <ul class="nav">
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link px-3 {{ request()->routeIs('home') ? 'active' : 'text-dark' }}">Trang Chủ</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('products.index') }}" class="nav-link px-3 {{ request()->routeIs('products.*') ? 'active' : 'text-dark' }}">Sản Phẩm</a>
                </li>
            </ul>

            <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                <form action="{{ route('products.index') }}" method="GET" class="d-flex client-search" role="search">
                    <input type="search" name="search" class="form-control" placeholder="Tìm kiếm đồ uống..." aria-label="Tìm kiếm sản phẩm">
                    <button type="submit" class="btn btn-primary ms-2">Tìm</button>
                </form>

                <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary cart-button position-relative" aria-label="Giỏ hàng">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 8.25h10.5l-.75 10.5a2.25 2.25 0 0 1-2.25 2.1h-6.5a2.25 2.25 0 0 1-2.25-2.1L4.75 8.25Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 8.25a3.25 3.25 0 0 1 6.5 0" />
                    </svg>
                    @if(session('cart'))
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ count(session('cart')) }}
                        </span>
                    @endif
                </a>

                @auth
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Tài khoản</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Đăng Xuất</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">Đăng Nhập</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Đăng Ký</a>
                @endauth
            </div>
        </nav>
    </header>

    <main style="min-height: 100vh;">
        @if(session('success'))
            <div class="container mt-4">
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="container mt-4">
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer text-white mt-5">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="brand-mark">C</span>
                        <h3 class="h5 fw-bold mb-0">Chill Drink</h3>
                    </div>
                    <p class="text-white-50 mb-0">Đồ uống tươi mát, giao nhanh và đặt hàng dễ dàng mỗi ngày.</p>
                </div>
                <div class="col-md-4">
                    <h3 class="h5 fw-bold mb-3">Liên Hệ</h3>
                    <ul class="list-unstyled text-white-50 mb-0">
                        <li class="mb-2">Hotline: 1900-xxxx</li>
                        <li class="mb-2">Email: contact@chilldrink.com</li>
                        <li>Địa chỉ: Hà Nội, Việt Nam</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h3 class="h5 fw-bold mb-3">Mạng Xã Hội</h3>
                    <div class="d-flex gap-3">
                        <a href="#" class="link-light link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover">Facebook</a>
                        <a href="#" class="link-light link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover">Instagram</a>
                        <a href="#" class="link-light link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover">Zalo</a>
                    </div>
                </div>
            </div>
            <div class="border-top border-secondary mt-4 pt-4 text-center text-white-50">
                <p class="mb-0">&copy; 2026 Chill Drink. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
