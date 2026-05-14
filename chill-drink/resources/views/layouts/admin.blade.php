<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin - {{ config('app.name', 'Chill Drink') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --admin-primary: #0f8b8d;
            --admin-primary-dark: #086972;
            --admin-ink: #172033;
            --admin-muted: #667085;
            --admin-bg: #f4f8fb;
            --admin-border: #dcebe8;
            --admin-shadow: 0 18px 45px rgba(23, 32, 51, 0.10);
        }

        body {
            font-family: Figtree, Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(15, 139, 141, 0.12), transparent 30rem),
                var(--admin-bg);
            color: var(--admin-ink);
        }

        .admin-shell {
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 270px;
            background:
                radial-gradient(circle at top left, rgba(59, 214, 181, 0.22), transparent 18rem),
                linear-gradient(180deg, #132033 0%, #0b363b 100%);
        }

        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.72);
            border-radius: 14px;
            font-weight: 700;
            padding: 0.85rem 1rem;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }

        .admin-brand-mark {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--admin-primary), #3bd6b5);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.18);
            font-weight: 800;
        }

        .admin-topbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(18px);
        }

        .admin-card {
            border: 1px solid var(--admin-border);
            border-radius: 20px;
            box-shadow: var(--admin-shadow);
        }

        .admin-table th {
            color: var(--admin-muted);
            font-size: 0.76rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .admin-thumb {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid var(--admin-border);
        }

        .btn-primary {
            --bs-btn-bg: var(--admin-primary);
            --bs-btn-border-color: var(--admin-primary);
            --bs-btn-hover-bg: var(--admin-primary-dark);
            --bs-btn-hover-border-color: var(--admin-primary-dark);
            border-radius: 999px;
            font-weight: 700;
        }

        .badge {
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .admin-shell {
                flex-direction: column;
            }

            .admin-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell d-flex">
        <aside class="admin-sidebar text-white p-3">
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none mb-4">
                <span class="admin-brand-mark">C</span>
                <span class="fs-5 fw-bold">Chill Admin</span>
            </a>

            <nav class="nav flex-column gap-1">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">Sản Phẩm</a>
                <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Danh Mục</a>
                <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Đơn Hàng</a>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Người Dùng</a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-outline-light w-100">Đăng Xuất</button>
            </form>
        </aside>

        <div class="flex-grow-1">
            <header class="admin-topbar border-bottom">
                <div class="container-fluid py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-0">Chill Drink Admin</p>
                        <h1 class="h4 fw-bold mb-0">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Xem web</a>
                        <div class="badge text-bg-primary rounded-pill px-3 py-2">{{ Auth::user()->name }}</div>
                    </div>
                </div>
            </header>

            <main class="container-fluid p-4">
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
</body>
</html>
