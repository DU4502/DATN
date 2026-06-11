@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Chill Drink') }} - @yield('title', 'Đồ Uống Online')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ─── Design Tokens ─── */
        :root {
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --c-primary: #0D9373;
            --c-primary-dark: #067A5F;
            --c-primary-light: #E6F7F2;
            --c-primary-glow: rgba(13, 147, 115, 0.15);
            --c-accent: #10B981;
            --c-ink: #111827;
            --c-ink-secondary: #374151;
            --c-muted: #6B7280;
            --c-subtle: #9CA3AF;
            --c-surface: #FFFFFF;
            --c-bg: #F9FAFB;
            --c-bg-warm: #F0FDF9;
            --c-border: #E5E7EB;
            --c-border-light: #F3F4F6;
            --c-danger: #EF4444;
            --c-chart-sunrise: #FDE68A;
            --c-chart-amber: #F59E0B;
            --c-chart-amber-dark: #D97706;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            --radius-full: 9999px;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            --shadow-glow: 0 0 20px rgba(13, 147, 115, 0.12);
        }

        /* ─── Base Reset ─── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            color: var(--c-ink);
            background: var(--c-bg);
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
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
            font-family: var(--font-sans) !important;
            letter-spacing: -0.011em !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .h1,
        .h2,
        .h3,
        .h4,
        .h5,
        .h6 {
            font-family: var(--font-sans);
            color: var(--c-ink);
            font-weight: 700;
            letter-spacing: -0.025em !important;
            line-height: 1.2;
        }

        p {
            color: var(--c-ink-secondary);
            line-height: 1.65;
        }

        a {
            color: var(--c-primary);
            transition: color 0.2s ease;
        }

        a:hover {
            color: var(--c-primary-dark);
        }

        /* ─── Buttons ─── */
        .btn {
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.625rem 1.25rem;
            min-height: 40px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1.5px solid transparent;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            --bs-btn-bg: var(--c-primary);
            --bs-btn-border-color: var(--c-primary);
            --bs-btn-hover-bg: var(--c-primary-dark);
            --bs-btn-hover-border-color: var(--c-primary-dark);
            --bs-btn-active-bg: var(--c-primary-dark);
            --bs-btn-active-border-color: var(--c-primary-dark);
            box-shadow: 0 1px 3px rgba(13, 147, 115, 0.3), 0 1px 2px rgba(13, 147, 115, 0.2);
        }

        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(13, 147, 115, 0.35);
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            --bs-btn-color: var(--c-primary);
            --bs-btn-border-color: var(--c-border);
            --bs-btn-hover-bg: var(--c-primary);
            --bs-btn-hover-border-color: var(--c-primary);
            --bs-btn-hover-color: #fff;
            background: var(--c-surface);
        }

        .text-primary {
            color: var(--c-primary) !important;
        }

        .bg-primary {
            background-color: var(--c-primary) !important;
        }

        /* ─── Form Controls ─── */
        .form-control,
        .form-select {
            border: 1.5px solid var(--c-border);
            border-radius: var(--radius-md);
            padding: 0.65rem 0.9rem;
            color: var(--c-ink);
            font-weight: 500;
            font-size: 0.9rem;
            background: var(--c-surface);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-label {
            color: var(--c-ink);
            font-weight: 600;
            font-size: 0.8125rem;
            margin-bottom: 0.375rem;
        }

        textarea.form-control {
            border-radius: var(--radius-md);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-primary-glow);
        }

        /* ─── Cards ─── */
        .card,
        .dropdown-menu,
        .list-group-item {
            border-color: var(--c-border) !important;
        }

        .drink-card {
            border: 1px solid var(--c-border);
            border-radius: var(--radius-xl);
            background: var(--c-surface);
            box-shadow: var(--shadow-sm);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                border-color 0.25s ease;
        }

        .drink-card:hover {
            transform: translateY(-4px);
            border-color: rgba(13, 147, 115, 0.2);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
        }

        .list-group-item {
            color: var(--c-ink);
            padding: 0.8rem 1rem;
            font-weight: 500;
            border-color: var(--c-border-light) !important;
        }

        .list-group-item:hover {
            color: var(--c-primary);
            background: var(--c-primary-light);
        }

        .list-group-item.active {
            color: #fff;
            background: var(--c-primary);
            border-color: var(--c-primary) !important;
        }

        .pagination {
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0;
        }

        .pagination .page-link {
            min-width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-full) !important;
            border: 1.5px solid var(--c-border);
            color: var(--c-primary);
            font-weight: 700;
            line-height: 1;
            box-shadow: none;
        }

        .pagination .page-link svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
            display: block;
            flex: 0 0 auto;
        }

        .pagination .page-item.active .page-link,
        .pagination .page-link:hover {
            color: #ffffff;
            background: var(--c-primary);
            border-color: var(--c-primary);
        }

        .pagination .page-item.disabled .page-link {
            color: var(--c-subtle);
            background: var(--c-bg);
            border-color: var(--c-border-light);
        }

        /* ─── Section Typography ─── */
        .section-kicker {
            color: var(--c-primary);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em !important;
            text-transform: uppercase;
        }

        .section-title {
            color: var(--c-ink);
            font-weight: 800;
            letter-spacing: -0.03em !important;
        }

        /* ─── Header ─── */
        .site-header {
            background: rgba(255, 255, 255, 0.82);
            border-bottom: 1px solid rgba(229, 231, 235, 0.6);
            backdrop-filter: blur(20px) saturate(1.8);
            -webkit-backdrop-filter: blur(20px) saturate(1.8);
            transition: padding 0.3s ease, box-shadow 0.3s ease;
        }

        .site-header.scrolled {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .site-header.scrolled .navbar {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--c-surface);
            border: 1.5px solid var(--c-border);
            font-weight: 800;
            font-size: 1rem;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
        }

        .brand-text {
            color: var(--c-ink);
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -0.03em !important;
        }

        /* ─── Navigation ─── */
        .nav-link {
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.5rem 0.875rem !important;
            color: var(--c-muted) !important;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--c-ink) !important;
            background: var(--c-border-light);
        }

        .nav-link.active {
            color: var(--c-primary) !important;
            background: var(--c-primary-light);
        }

        .client-search {
            width: clamp(200px, 22vw, 280px);
        }

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
            border: 1.5px solid var(--c-border);
            border-radius: var(--radius-sm);
            padding: 0.45rem 0.65rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px var(--c-primary-glow);
        }

        /* ─── Cart Button ─── */
        .cart-button {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--c-surface);
            border: 1.5px solid var(--c-border);
            color: var(--c-ink-secondary);
            padding: 0;
            transition: all 0.2s ease;
        }

        .cart-button:hover {
            border-color: var(--c-primary);
            color: var(--c-primary);
            background: var(--c-primary-light);
        }

        .cart-button svg {
            width: 18px;
            height: 18px;
            display: block;
            flex: 0 0 auto;
        }

        .cart-button i {
            display: block;
            flex: 0 0 auto;
            font-size: 1.05rem;
            line-height: 1;
        }

        .cart-bump {
            animation: cartBump 0.55s ease;
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
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--c-primary);
            color: #fff;
            box-shadow: 0 8px 24px rgba(13, 147, 115, 0.35);
            pointer-events: none;
            transform: translate(-50%, -50%) scale(1);
            transition: transform 0.72s cubic-bezier(.22, .88, .22, 1), opacity 0.72s ease;
        }

        .cart-feedback {
            position: fixed;
            right: 1.25rem;
            top: 5rem;
            z-index: 1081;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            max-width: min(340px, calc(100vw - 2rem));
            padding: 0.7rem 1rem;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            background: var(--c-surface);
            color: var(--c-ink);
            box-shadow: var(--shadow-xl);
            font-weight: 600;
            font-size: 0.875rem;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.25s ease, transform 0.25s ease;
            pointer-events: none;
        }

        .cart-feedback.show {
            opacity: 1;
            transform: translateY(0);
        }

        .cart-feedback-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--c-primary-light);
            color: var(--c-primary);
            flex: 0 0 auto;
        }

        [data-ajax-cart].is-adding button[type="submit"],
        [data-ajax-cart].is-added button[type="submit"] {
            transform: translateY(-1px);
        }

        @keyframes cartBump {
            0% {
                transform: scale(1);
            }

            35% {
                transform: scale(1.12);
            }

            70% {
                transform: scale(0.96);
            }

            100% {
                transform: scale(1);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .cart-button.cart-bump,
            .cart-fly-dot,
            .cart-feedback {
                animation: none;
                transition: none;
            }
        }

        /* ─── Avatar & Profile ─── */
        .user-avatar {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--c-border);
            border-radius: 50%;
            background: linear-gradient(135deg, var(--c-primary-light), var(--c-primary));
            color: #fff;
            font-weight: 700;
            font-size: 0.875rem;
            overflow: hidden;
            transition: all 0.2s ease;
            padding: 0;
        }

        .user-avatar:hover,
        .user-avatar.show {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-primary-glow);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-preset-mint {
            background: linear-gradient(135deg, #A7F3D0, #059669);
        }

        .avatar-preset-sky {
            background: linear-gradient(135deg, #BAE6FD, #0284C7);
        }

        .avatar-preset-berry {
            background: linear-gradient(135deg, #FBCFE8, #BE185D);
        }

        .avatar-preset-orange {
            background: linear-gradient(135deg, #FED7AA, #EA580C);
        }

        .user-avatar::after {
            display: none;
        }

        /* ─── Notification ─── */
        .notification-button {
            position: relative;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-muted);
            transition: all 0.2s ease;
        }

        .notification-button:hover,
        .notification-button.show {
            border-color: var(--c-primary);
            color: var(--c-primary);
            background: var(--c-primary-light);
        }

        .notification-button::after {
            display: none;
        }

        .notification-dot {
            position: absolute;
            top: 6px;
            right: 7px;
            width: 8px;
            height: 8px;
            border: 1.5px solid var(--c-surface);
            border-radius: 50%;
            background: var(--c-danger);
        }

        .notification-menu {
            width: min(380px, calc(100vw - 2rem));
            margin-top: 0.5rem !important;
            padding: 0;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }

        .notification-head {
            padding: 1rem 1.15rem;
            background: var(--c-bg);
            border-bottom: 1px solid var(--c-border);
        }

        .notification-list {
            max-height: 330px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.9rem 1.15rem;
            border-bottom: 1px solid var(--c-border-light);
            background: var(--c-surface);
            transition: background 0.15s;
        }

        .notification-item:hover {
            background: var(--c-bg);
        }

        .notification-item:last-child {
            border-bottom: 0;
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: var(--radius-sm);
            background: var(--c-primary-light);
            color: var(--c-primary);
            font-size: 0.9rem;
        }

        .notification-time {
            color: var(--c-subtle);
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* ─── Profile Menu ─── */
        .profile-menu {
            min-width: 200px;
            margin-top: 0.4rem !important;
            padding: 0.4rem;
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }

        .dropdown:hover>.profile-menu:not(.show) {
            display: none !important;
        }

        .profile-menu.show {
            display: block;
        }

        .profile-menu .dropdown-item {
            color: var(--c-ink-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.55rem 0.85rem;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
        }

        .profile-menu .dropdown-item:hover,
        .profile-menu .dropdown-item:focus {
            color: var(--c-primary);
            background: var(--c-primary-light);
        }

        .profile-menu form {
            margin: 0;
        }

        /* ─── Profile Tabs ─── */
        .profile-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-tab {
            border: 1.5px solid var(--c-border);
            border-radius: var(--radius-full);
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--c-muted);
            text-decoration: none;
            background: var(--c-surface);
            transition: all 0.2s ease;
        }

        .profile-tab:hover,
        .profile-tab.active {
            border-color: var(--c-primary);
            color: var(--c-primary);
            background: var(--c-primary-light);
        }

        /* ─── Auth Pages ─── */
        .auth-page {
            padding: 3rem 0 5rem;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }

        .auth-card {
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--c-border);
            background: var(--c-surface);
        }

        .auth-brand-mark {
            width: 52px;
            height: 52px;
            margin-left: auto;
            margin-right: auto;
            display: block;
            border-radius: var(--radius-lg);
        }

        .auth-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--c-subtle);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--c-border);
        }

        .auth-social-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .auth-social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1rem;
            border: 1.5px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-ink-secondary);
            font-weight: 600;
            font-size: 0.8125rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .auth-social-btn:hover {
            border-color: var(--c-muted);
            background: var(--c-bg);
        }

        .auth-social-btn.facebook:hover {
            color: #1877F2;
            border-color: #1877F2;
        }

        .auth-social-btn.google:hover {
            color: #EA4335;
            border-color: #EA4335;
        }

        .auth-section-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 0.8125rem;
            color: var(--c-primary);
            letter-spacing: 0.02em !important;
            text-transform: uppercase;
        }

        .auth-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .auth-form-grid .full-span {
            grid-column: 1 / -1;
        }

        .auth-note {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            background: var(--c-primary-light);
            color: var(--c-primary-dark);
            font-size: 0.8125rem;
            font-weight: 500;
        }

        @media (max-width: 575.98px) {
            .auth-form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ─── Footer ─── */
        .site-footer {
            position: relative;
            overflow: hidden;
            color: var(--c-ink) !important;
            background: var(--c-surface);
            border-top: 1px solid var(--c-border);
        }

        .site-footer .container {
            position: relative;
            z-index: 1;
        }

        .footer-link {
            color: var(--c-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: var(--c-primary);
        }

        .footer-social-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--c-bg);
            color: var(--c-muted);
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .footer-social-btn:hover {
            background: var(--c-primary);
            color: #fff;
            transform: translateY(-2px);
        }

        .footer-heading {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em !important;
            text-transform: uppercase;
            color: var(--c-ink);
            margin-bottom: 1rem;
        }

        .footer-bottom {
            border-top: 1px solid var(--c-border);
            padding-top: 1.5rem;
            margin-top: 2rem;
        }

        /* ─── Nav Actions ─── */
        .nav-actions .btn-outline-primary,
        .nav-actions .btn-primary {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* ─── Responsive ─── */
        @media (max-width: 991.98px) {
            .client-search {
                width: 100%;
                margin-top: 0.75rem;
            }

            .navbar-collapse {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--c-border);
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

        main {
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ─── Mini Bar Chart ─── */
        /* COMMENTED OUT - Mini chart decoration (not used) - Có thể xóa sau
        .mini-chart {
            position: relative;
            display: inline-flex;
            align-items: flex-end;
            gap: 0.4rem;
            padding: 0.75rem 1rem 0.6rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(251, 191, 36, 0.18);
            background: linear-gradient(180deg, rgba(254, 243, 199, 0.55), rgba(255, 255, 255, 0.9));
            box-shadow: 0 8px 20px rgba(234, 179, 8, 0.12);
            min-width: 132px;
        }

        .mini-chart::before {
            content: '';
            position: absolute;
            inset: 12px;
            border-radius: inherit;
            background: radial-gradient(circle at top, rgba(253, 224, 71, 0.35), transparent 70%);
            opacity: 0.75;
        }

        .mini-chart-bar {
            position: relative;
            width: 12px;
            border-radius: 999px 999px 4px 4px;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 60%, #d97706 100%);
            filter: drop-shadow(0 4px 6px rgba(217, 119, 6, 0.15));
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .mini-chart-bar:nth-child(3n) {
            background: linear-gradient(180deg, #fde68a 0%, #fbbf24 55%, #f59e0b 100%);
        }

        .mini-chart:hover .mini-chart-bar {
            opacity: 0.88;
        }

        .mini-chart-bar::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.65), rgba(255, 255, 255, 0) 65%);
            mix-blend-mode: screen;
        }

        .mini-chart-bar[data-h="xl"] {
            height: 54px;
        }

        .mini-chart-bar[data-h="lg"] {
            height: 48px;
        }

        .mini-chart-bar[data-h="md"] {
            height: 38px;
        }

        .mini-chart-bar[data-h="sm"] {
            height: 30px;
        }

        @media (max-width: 767.98px) {
            .mini-chart {
                padding: 0.6rem 0.75rem 0.4rem;
                gap: 0.3rem;
                min-width: 110px;
            }

            .mini-chart-bar {
                width: 10px;
            }

            .mini-chart-bar[data-h="xl"] {
                height: 48px;
            }

            .mini-chart-bar[data-h="lg"] {
                height: 40px;
            }

            .mini-chart-bar[data-h="md"] {
                height: 32px;
            }

            .mini-chart-bar[data-h="sm"] {
                height: 24px;
            }
        }
        END COMMENT */
    </style>
</head>

<body>
    <header class="site-header sticky-top" id="siteHeader">
        <nav class="navbar navbar-expand-md container py-2">
            <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-2 fw-bold m-0">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="brand-mark" style="object-fit: contain; padding: 2px;">
                <span class="brand-text">Chill Drink</span>
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#clientNavbar" aria-controls="clientNavbar" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse flex-grow-1" id="clientNavbar">
                <ul class="navbar-nav ms-lg-4 gap-lg-1">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">Sản Phẩm</a>
                    </li>
                </ul>

                {{-- COMMENTED OUT - Mini chart decoration (not used) - Có thể xóa sau
                <div class="d-none d-lg-flex align-items-end ms-lg-5 me-lg-4" aria-hidden="true">
                    <div class="mini-chart">
                        <span class="mini-chart-bar" data-h="sm"></span>
                        <span class="mini-chart-bar" data-h="md"></span>
                        <span class="mini-chart-bar" data-h="lg"></span>
                        <span class="mini-chart-bar" data-h="md"></span>
                        <span class="mini-chart-bar" data-h="xl"></span>
                    </div>
                </div>
                --}}

                <div class="nav-actions d-flex flex-wrap align-items-center gap-2 ms-lg-auto mt-3 mt-lg-0">
                    <form action="{{ route('products.index') }}" method="GET" class="d-flex client-search gap-2" role="search">
                        <div class="position-relative flex-grow-1">
                            <i class="bi bi-search position-absolute" style="left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--c-subtle); font-size: 0.85rem;"></i>
                            <input type="search" name="search" class="form-control" placeholder="Tìm đồ uống..." aria-label="Tìm kiếm sản phẩm" value="{{ request('search') }}" style="padding-left: 2.4rem; border-radius: var(--radius-md);">
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Tìm</button>
                    </form>

                    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary cart-button position-relative" aria-label="Giỏ hàng" data-cart-button>
                        <i class="bi bi-cart-plus" aria-hidden="true"></i>
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
                            <i class="bi bi-bell" style="font-size: 1.05rem;"></i>
                            <span class="notification-dot" aria-hidden="true"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-menu">
                            <div class="notification-head">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">Thông báo</div>
                                        <div class="text-secondary" style="font-size: 0.8rem;">Cập nhật đơn hàng của bạn</div>
                                    </div>
                                    <span class="badge rounded-pill" style="background: var(--c-primary-light); color: var(--c-primary); font-size: 0.7rem;">3 mới</span>
                                </div>
                            </div>
                            <div class="notification-list">
                                <div class="notification-item">
                                    <span class="notification-icon"><i class="bi bi-truck"></i></span>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.85rem;">Shipper sắp đến</div>
                                        <div class="text-secondary" style="font-size: 0.8rem;">Đơn hàng đang ở gần địa chỉ nhận.</div>
                                        <div class="notification-time mt-1">Vừa xong</div>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-icon"><i class="bi bi-cup-straw"></i></span>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.85rem;">Đơn đang được giao</div>
                                        <div class="text-secondary" style="font-size: 0.8rem;">Đồ uống đã rời cửa hàng.</div>
                                        <div class="notification-time mt-1">10 phút trước</div>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-icon"><i class="bi bi-check2-circle"></i></span>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.85rem;">Giao hàng thành công</div>
                                        <div class="text-secondary" style="font-size: 0.8rem;">Cảm ơn bạn đã đặt tại Chill Drink.</div>
                                        <div class="notification-time mt-1">Hôm nay</div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 border-top">
                                <a href="{{ route('orders.index') }}" class="btn btn-primary w-100 btn-sm">Xem đơn hàng</a>
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
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Tài khoản</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>Đơn hàng</a></li>
                            <li>
                                <hr class="dropdown-divider" style="margin: 0.25rem 0;">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Đăng Xuất</button>
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
            <div class="alert alert-danger mb-0" style="border-radius: var(--radius-md);">{{ session('error') }}</div>
        </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer mt-5">
        <div class="container py-5">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="brand-mark" style="object-fit: contain; padding: 2px;">
                        <span class="brand-text">Chill Drink</span>
                    </div>
                    <p class="text-secondary mb-4" style="font-size: 0.875rem; max-width: 300px;">Đồ uống tươi mát, giao nhanh tận nơi. Đặt hàng dễ dàng mỗi ngày với Chill Drink.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="footer-social-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="footer-social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="footer-social-btn" aria-label="Tiktok"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h3 class="footer-heading">Sản phẩm</h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('products.index') }}" class="footer-link">Tất cả</a>
                        <a href="#" class="footer-link">Trà sữa</a>
                        <a href="#" class="footer-link">Cà phê</a>
                        <a href="#" class="footer-link">Nước ép</a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h3 class="footer-heading">Hỗ trợ</h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="#" class="footer-link">Liên hệ</a>
                        <a href="#" class="footer-link">Câu hỏi thường gặp</a>
                        <a href="#" class="footer-link">Chính sách đổi trả</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h3 class="footer-heading">Liên hệ</h3>
                    <div class="d-flex flex-column gap-2">
                        <span class="footer-link" style="cursor: default;">
                            <i class="bi bi-telephone me-2"></i>1900-xxxx
                        </span>
                        <span class="footer-link" style="cursor: default;">
                            <i class="bi bi-envelope me-2"></i>contact@chilldrink.com
                        </span>
                        <span class="footer-link" style="cursor: default;">
                            <i class="bi bi-geo-alt me-2"></i>Hà Nội, Việt Nam
                        </span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom text-center">
                <p class="mb-0 text-secondary" style="font-size: 0.8rem;">&copy; 2026 Chill Drink. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* Header shrink on scroll */
        const header = document.getElementById('siteHeader');
        if (header) {
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        header.classList.toggle('scrolled', window.scrollY > 20);
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (window.bootstrap) {
                return;
            }

            const navbarToggler = document.querySelector('[data-bs-target="#clientNavbar"]');
            const clientNavbar = document.getElementById('clientNavbar');

            navbarToggler?.addEventListener('click', function() {
                const isOpen = clientNavbar?.classList.toggle('show');
                navbarToggler.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const menu = button.parentElement?.querySelector('.dropdown-menu');
                    const willOpen = !menu?.classList.contains('show');

                    document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
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

            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    menu.classList.remove('show');
                    const button = menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
                    button?.classList.remove('show');
                    button?.setAttribute('aria-expanded', 'false');
                });
            });
        });

        document.addEventListener('submit', async function(event) {
            const form = event.target;

            if (!form.matches('[data-ajax-cart]')) {
                return;
            }

            if (event.submitter?.name === 'buy_now') {
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

                const isIconButton = submitter.classList.contains('add-round') || submitter.getAttribute('aria-label') === 'Thêm vào giỏ';
                const hasText = submitter.textContent.trim().length > 0 && !isIconButton;

                if (state === 'loading' && isIconButton) {
                    submitter.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
                }

                if (state === 'success' && isIconButton) {
                    submitter.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
                }

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

                    document.dispatchEvent(new CustomEvent('cart:updated', {
                        detail: data
                    }));
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
