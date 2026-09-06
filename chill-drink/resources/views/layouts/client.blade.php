@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Chill Drink') }} - @yield('title', 'Đồ Uống Online')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/bootstrap-local.css', 'resources/js/app.js'])

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

        html {
            width: 100%;
            max-width: 100%;
            overflow-x: clip;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        body {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            overflow-x: clip;
            font-family: var(--font-sans);
            color: var(--c-ink);
            background: var(--c-bg);
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        img,
        svg,
        video,
        canvas {
            max-width: 100%;
        }

        main,
        .container,
        .container-fluid,
        .row,
        [class*="col-"] {
            min-width: 0;
        }

        :where(h1, h2, h3, h4, h5, h6, p, a, button, label, td, th) {
            overflow-wrap: break-word;
        }

        .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .modal-dialog {
            width: min(var(--bs-modal-width, 500px), calc(100vw - 1.5rem));
            max-width: calc(100vw - 1.5rem);
        }

        .modal-content {
            max-height: calc(100dvh - 1.5rem);
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .modal-body {
            min-width: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .modal-footer {
            flex-wrap: wrap;
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

        .header-branch-button {
            min-height: 40px;
            max-width: 190px;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.38rem 0.7rem;
            border: 1.5px solid var(--c-border);
            border-radius: var(--radius-md);
            background: var(--c-surface);
            color: var(--c-ink);
            text-align: left;
            transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        button.header-branch-button:hover {
            border-color: var(--c-primary);
            background: var(--c-primary-light);
            box-shadow: 0 6px 16px rgba(13, 147, 115, 0.12);
        }

        .header-branch-button__icon {
            color: var(--c-primary);
            font-size: 1rem;
            flex: 0 0 auto;
        }

        .header-branch-button__copy {
            min-width: 0;
            display: block;
            line-height: 1.1;
        }

        .header-branch-button__copy small,
        .header-branch-button__copy strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .header-branch-button__copy small {
            margin-bottom: 0.12rem;
            color: var(--c-muted);
            font-size: 0.62rem;
            font-weight: 600;
        }

        .header-branch-button__copy strong {
            font-size: 0.75rem;
            font-weight: 800;
        }

        .header-branch-button__chevron {
            color: var(--c-muted);
            font-size: 0.72rem;
            flex: 0 0 auto;
        }

        .branch-switch-modal .modal-dialog { max-width: 560px; }
        .branch-switch-modal .modal-content {
            max-height: calc(100dvh - 2rem);
            overflow: hidden;
            border: 0;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(8, 42, 38, 0.24);
        }
        .branch-switch-modal .modal-header { padding: 1.35rem 1.4rem 0.85rem; }
        .branch-switch-modal .modal-body { padding: 0.5rem 1.4rem 1.4rem; overflow-y: auto; }
        .branch-option-list { display: grid; gap: 0.65rem; }
        .branch-option {
            width: 100%;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.8rem;
            padding: 0.9rem 1rem;
            border: 1.5px solid var(--c-border);
            border-radius: 15px;
            background: var(--c-surface);
            color: var(--c-ink);
            text-align: left;
            transition: border-color 0.18s ease, background 0.18s ease, transform 0.18s ease;
        }
        .branch-option:not(:disabled):hover {
            border-color: var(--c-primary);
            background: var(--c-primary-light);
            transform: translateY(-1px);
        }
        .branch-option.is-current {
            border-color: var(--c-primary);
            background: var(--c-primary-light);
        }
        .branch-option__icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--c-surface);
            color: var(--c-primary);
            box-shadow: inset 0 0 0 1px var(--c-border);
        }
        .branch-option__copy { min-width: 0; }
        .branch-option__copy strong,
        .branch-option__copy small { display: block; }
        .branch-option__copy small {
            margin-top: 0.15rem;
            overflow: hidden;
            color: var(--c-muted);
            font-size: 0.75rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .branch-option__state {
            color: var(--c-primary-dark);
            font-size: 0.7rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .active-group-return {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .45rem .8rem;
            border: 1px solid #9edbce;
            border-radius: 999px;
            color: var(--c-primary-dark);
            background: #e9f9f5;
            font-size: .78rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
        }
        button.active-group-return { font-family: inherit; cursor: pointer; }

        .active-group-return:hover { color: var(--c-primary-dark); background: #d9f5ee; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(13,147,115,.14); }
        .active-group-return__time { min-width: 3.15rem; font-variant-numeric: tabular-nums; }
        .active-group-return__members { display: inline-flex; align-items: center; gap: .25rem; padding-left: .5rem; border-left: 1px solid #acdcd1; }
        .active-group-return.is-checkout { color: #92400e; border-color: #f6c76d; background: #fff8e8; }
        .active-group-return.is-checkout:hover { color: #78350f; background: #ffefc7; }

        @media (min-width: 1400px) {
            .site-header .navbar {
                flex-wrap: nowrap;
            }

            #clientNavbar.navbar-collapse {
                display: flex !important;
                flex-basis: auto;
                flex-grow: 1;
                min-width: 0;
                align-items: center;
                visibility: visible !important;
            }

            #clientNavbar .navbar-nav {
                flex: 0 0 auto;
            }

            #clientNavbar .nav-link {
                white-space: nowrap;
            }

            .nav-actions {
                min-width: 0;
                flex-wrap: nowrap !important;
            }

            .nav-actions.has-group-action {
                gap: .45rem !important;
            }

            .nav-actions.has-group-action .active-group-return,
            .nav-actions.has-group-action > form,
            .nav-actions.has-group-action .favorite-nav-button,
            .nav-actions.has-group-action > [data-cart-button],
            .nav-actions.has-group-action > .dropdown {
                flex: 0 0 auto;
            }

            .nav-actions.has-group-action .client-search {
                width: clamp(180px, 18vw, 240px);
                flex: 0 1 240px;
            }

            .nav-actions.has-group-action .header-branch-button {
                max-width: 165px;
            }
        }

        @media (min-width: 1400px) and (max-width: 1919.98px) {
            .nav-actions.has-group-action .active-group-return__label {
                display: none !important;
            }

            .nav-actions.has-group-action .active-group-return {
                padding-inline: .7rem;
            }
        }

        @media (max-width: 1399.98px) {
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

        .favorite-nav-button.active,
        .favorite-nav-button.active:hover,
        .favorite-nav-button.active:focus {
            border-color: var(--c-border);
            color: #e83e5b;
            background: var(--c-surface);
            box-shadow: none;
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
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.85rem;
            width: min(440px, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid var(--c-border);
            border-radius: 20px;
            background: var(--c-surface);
            color: var(--c-ink);
            box-shadow: 0 22px 55px rgba(7, 42, 38, .2);
            font-size: 0.875rem;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.25s ease, transform 0.25s ease;
            pointer-events: none;
        }

        body.cart-warning-open { overflow: hidden; }
        body.cart-warning-open::after {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 1089;
            background: rgba(10, 28, 25, .48);
            backdrop-filter: blur(4px);
        }
        .cart-feedback.is-warning {
            left: 50%;
            right: auto;
            top: 50%;
            z-index: 1090;
            grid-template-columns: 1fr;
            justify-items: center;
            width: min(470px, calc(100vw - 2rem));
            padding: 1.75rem;
            border: 0;
            border-radius: 28px;
            background: linear-gradient(160deg, #ffffff 0%, #fffdf8 100%);
            text-align: center;
            box-shadow: 0 30px 90px rgba(5, 28, 24, .32);
            transform: translate(-50%, calc(-50% - 12px)) scale(.97);
        }
        .cart-feedback-copy { min-width: 0; line-height: 1.5; }
        .cart-feedback-copy strong { display: block; margin-bottom: .15rem; font-size: .95rem; }
        .cart-feedback-copy span { display: block; color: var(--c-muted); font-weight: 500; }
        .cart-feedback-actions { display: flex; align-items: center; gap: .45rem; }
        .cart-feedback-action { min-height: 40px; padding-inline: 1rem !important; white-space: nowrap; }
        .cart-feedback-close { width: 34px; height: 34px; border: 0; border-radius: 50%; background: #f3f5f4; color: #66736f; }

        .cart-feedback.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .cart-feedback-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--c-primary-light);
            color: var(--c-primary);
            flex: 0 0 auto;
        }

        .cart-feedback.is-warning.show { transform: translate(-50%, -50%) scale(1); }
        .cart-feedback.is-warning .cart-feedback-icon {
            width: 58px;
            height: 58px;
            margin-bottom: .15rem;
            border-radius: 18px;
            background: linear-gradient(135deg, #fff0c7, #ffe1a1);
            color: #b85c00;
            font-size: 1.45rem;
            box-shadow: 0 10px 24px rgba(205, 119, 20, .18);
        }
        .cart-feedback.is-warning .cart-feedback-copy strong { margin-bottom: .45rem; font-size: 1.25rem; color: #17231f; }
        .cart-feedback.is-warning .cart-feedback-copy span { max-width: 370px; font-size: .95rem; line-height: 1.65; }
        .cart-feedback.is-warning .cart-feedback-actions { width: 100%; justify-content: center; margin-top: .35rem; }
        .cart-feedback.is-warning .cart-feedback-action { min-height: 46px; padding-inline: 1.35rem !important; }
        .cart-feedback.is-warning .cart-feedback-close { width: auto; min-height: 46px; padding: 0 1.2rem; border-radius: 999px; font-weight: 700; }

        @media (max-width: 575.98px) {
            .cart-feedback { right: 1rem; top: 4.75rem; grid-template-columns: 38px minmax(0, 1fr); width: calc(100vw - 2rem); }
            .cart-feedback-actions { grid-column: 1 / -1; justify-content: flex-end; }
            .cart-feedback.is-warning { left: 50%; right: auto; top: 50%; padding: 1.4rem 1.1rem; }
            .cart-feedback.is-warning .cart-feedback-actions { grid-column: auto; flex-direction: column; }
            .cart-feedback.is-warning .cart-feedback-actions > * { width: 100%; }
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
            text-decoration: none;
            color: inherit;
        }

        .notification-item.is-clickable {
            cursor: pointer;
        }

        .notification-item:hover,
        .notification-item.is-clickable:hover {
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

        .checkout-return-bar {
            position: fixed;
            right: 50%;
            bottom: 1rem;
            z-index: 1045;
            display: flex;
            align-items: center;
            gap: .8rem;
            width: min(520px, calc(100vw - 2rem));
            padding: .65rem .7rem .65rem 1rem;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 18px;
            color: #fff;
            background: rgba(5, 77, 64, .96);
            box-shadow: 0 16px 42px rgba(5, 57, 48, .28);
            backdrop-filter: blur(14px);
            transform: translateX(50%);
        }

        .checkout-return-bar__copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .checkout-return-bar__copy > strong,
        .checkout-return-bar__copy > span {
            display: block;
        }

        .checkout-return-bar__copy > strong { font-size: .82rem; }
        .checkout-return-bar__copy > span { color: rgba(255,255,255,.72); font-size: .7rem; }

        .checkout-return-bar__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 42px;
            padding: .55rem .9rem;
            border-radius: 12px;
            color: #06735f;
            background: #fff;
            font-size: .76rem;
            font-weight: 850;
            text-decoration: none;
            white-space: nowrap;
        }

        .checkout-return-bar__button:hover { color: #044f42; background: #e8fff8; }

        body:has(.checkout-return-bar) .client-chatbox {
            bottom: 6.2rem !important;
        }

        @media (max-width: 575.98px) {
            .checkout-return-bar {
                bottom: max(.5rem, env(safe-area-inset-bottom));
                gap: .55rem;
                width: calc(100vw - 1rem);
                padding: .5rem .5rem .5rem .7rem;
                border-radius: 14px;
            }

            .checkout-return-bar__copy > strong { font-size: .72rem; }
            .checkout-return-bar__copy > span { font-size: .62rem; }
            .checkout-return-bar__button { min-height: 38px; padding: .45rem .65rem; font-size: .68rem; }
            body:has(.checkout-return-bar) .client-chatbox { bottom: 5.2rem !important; }
        }

        /* Footer responsive: giữ nguyên giao diện desktop từ 992px trở lên. */
        @media (max-width: 991.98px) {
            .site-footer {
                margin-top: 2rem !important;
                background:
                    radial-gradient(circle at 100% 0, rgba(13, 147, 115, 0.10), transparent 34%),
                    linear-gradient(180deg, #ffffff 0%, #f5fbf9 100%);
                border-top-color: rgba(13, 147, 115, 0.16);
            }

            .site-footer .container {
                padding-top: 2rem !important;
                padding-bottom: calc(1.75rem + env(safe-area-inset-bottom)) !important;
            }

            .site-footer .row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem !important;
                margin: 0;
            }

            .site-footer .row > [class*="col-"] {
                width: auto;
                max-width: none;
                padding: 0;
            }

            .site-footer .row > :first-child,
            .site-footer .row > :last-child {
                grid-column: 1 / -1;
            }

            .site-footer .row > :first-child {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.85rem 1rem;
                padding: 1rem;
                border: 1px solid rgba(13, 147, 115, 0.14);
                border-radius: 18px;
                background: rgba(230, 247, 242, 0.72);
            }

            .site-footer .row > :first-child > .d-flex:first-child {
                grid-column: 1;
                margin-bottom: 0 !important;
            }

            .site-footer .row > :first-child > p {
                grid-column: 2;
                max-width: none !important;
                margin-bottom: 0 !important;
                line-height: 1.55;
            }

            .site-footer .row > :first-child > .d-flex:last-child {
                grid-column: 3;
            }

            .site-footer .row > :nth-child(2),
            .site-footer .row > :nth-child(3),
            .site-footer .row > :last-child {
                padding: 1rem;
                border: 1px solid var(--c-border);
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.86);
                box-shadow: 0 8px 24px rgba(9, 61, 51, 0.04);
            }

            .site-footer .footer-heading {
                margin-bottom: 0.7rem;
                color: var(--c-primary-dark);
            }

            .site-footer .footer-link {
                font-size: 0.82rem;
                line-height: 1.35;
            }

            .site-footer .footer-bottom {
                margin-top: 1rem;
                padding-top: 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .site-footer {
                border-radius: 20px 20px 0 0;
            }

            .site-footer .container {
                padding-top: 1rem !important;
                padding-bottom: calc(1rem + env(safe-area-inset-bottom)) !important;
            }

            .site-footer .row {
                gap: 0.7rem !important;
            }

            .site-footer .row > :first-child {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 0.65rem;
                padding: 0.85rem;
                border-radius: 15px;
            }

            .site-footer .row > :first-child > .d-flex:first-child {
                grid-column: 1;
            }

            .site-footer .row > :first-child > .d-flex:first-child .brand-mark {
                width: 42px;
                height: 42px;
                border-radius: 11px;
            }

            .site-footer .row > :first-child > .d-flex:first-child .brand-text {
                font-size: 1.05rem;
            }

            .site-footer .row > :first-child > p {
                grid-column: 1 / -1;
                grid-row: 2;
                font-size: 0.76rem !important;
                line-height: 1.5;
            }

            .site-footer .row > :first-child > .d-flex:last-child {
                grid-column: 2;
                grid-row: 1;
                gap: 0.35rem !important;
            }

            .site-footer .footer-social-btn {
                width: 32px;
                height: 32px;
                border-radius: 9px;
                font-size: 0.86rem;
            }

            .site-footer .row > :nth-child(2),
            .site-footer .row > :nth-child(3),
            .site-footer .row > :last-child {
                padding: 0.8rem;
                border-radius: 14px;
            }

            .site-footer .footer-heading {
                margin-bottom: 0.55rem;
                font-size: 0.66rem;
            }

            .site-footer .row > :nth-child(2) .d-flex,
            .site-footer .row > :nth-child(3) .d-flex {
                gap: 0.45rem !important;
            }

            .site-footer .footer-link {
                font-size: 0.75rem;
                overflow-wrap: anywhere;
            }

            .site-footer .row > :last-child .d-flex {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.55rem !important;
            }

            .site-footer .row > :last-child .footer-link {
                display: flex;
                align-items: flex-start;
                min-width: 0;
            }

            .site-footer .row > :last-child .footer-link:nth-child(2) {
                word-break: break-word;
            }

            .site-footer .row > :last-child .footer-link:last-child {
                grid-column: 1 / -1;
            }

            .site-footer .footer-bottom {
                min-height: 58px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-top: 0.75rem;
                padding: 0.8rem 3.6rem 0;
            }

            .site-footer .footer-bottom p {
                font-size: 0.7rem !important;
                line-height: 1.4;
            }
        }

        @media (max-width: 374.98px) {
            .site-footer .row > :first-child > .d-flex:last-child {
                grid-column: 1 / -1;
                grid-row: 3;
            }

            .site-footer .row > :last-child .d-flex {
                grid-template-columns: 1fr;
            }

            .site-footer .row > :last-child .footer-link:last-child {
                grid-column: auto;
            }
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
        @media (max-width: 1399.98px) {
            .client-search {
                width: 100%;
                margin-top: 0;
            }

            .navbar-collapse {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid var(--c-border);
            }

            #clientNavbar .navbar-nav {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.35rem;
                width: 100%;
            }

            #clientNavbar .nav-link {
                min-height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0.4rem 0.35rem !important;
                text-align: center;
                line-height: 1.2;
            }

            .nav-actions {
                width: 100%;
                display: grid !important;
                grid-template-columns: 40px 40px repeat(2, minmax(0, 1fr));
                align-items: center !important;
                gap: 0.5rem !important;
            }

            .nav-actions > .client-search,
            .nav-actions > .header-branch-button,
            .nav-actions > .active-group-return,
            .nav-actions > form:has(.active-group-return) {
                grid-column: 1 / -1;
            }

            .nav-actions > .header-branch-button {
                width: 100%;
                max-width: none;
                justify-content: center;
            }

            .nav-actions > .favorite-nav-button { grid-column: 1; }
            .nav-actions > [data-cart-button] { grid-column: 2; }
            .nav-actions > .notification-dropdown { grid-column: 3; }
            .nav-actions > .profile-dropdown { grid-column: 4; }
            .nav-actions > .nav-login-button { grid-column: 3; }
            .nav-actions > .nav-register-button { grid-column: 4; }

            .nav-actions:has(.profile-dropdown) {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .nav-actions .dropdown {
                width: auto;
                min-width: 0;
            }

            .nav-actions .nav-login-button,
            .nav-actions .nav-register-button {
                width: 100%;
                min-width: 0;
                padding-inline: 0.45rem;
                font-size: 0.78rem;
            }

            .notification-button {
                margin-inline: auto;
            }

            .nav-actions .dropdown .user-avatar {
                margin-left: auto;
                margin-right: auto;
            }
        }

        @media (max-width: 575.98px) {
            #clientNavbar .nav-link {
                min-height: 36px;
                font-size: 0.72rem;
            }

            .nav-actions {
                margin-top: 0.4rem !important;
                gap: 0.4rem !important;
                position: relative;
            }

            .client-search .form-control {
                min-height: 40px;
                padding-block: 0.4rem;
                font-size: 0.8rem;
            }

            .client-search .btn {
                min-width: 70px;
                min-height: 40px;
                padding: 0.4rem 0.7rem !important;
                font-size: 0.78rem;
            }

            .nav-actions .nav-login-button,
            .nav-actions .nav-register-button {
                min-height: 40px;
                padding-inline: 0.15rem;
                font-size: 0.68rem;
                white-space: nowrap;
            }

            .notification-dropdown {
                position: static;
            }

            .notification-dropdown .notification-menu {
                top: 100% !important;
                right: 0 !important;
                left: 0 !important;
                width: auto !important;
                max-width: none;
                margin-top: 0.4rem !important;
                transform: none !important;
                border-radius: 12px;
            }

            .notification-dropdown .notification-list {
                max-height: min(330px, calc(100dvh - 370px));
            }

            body:has(.notification-menu.show) .client-chatbox {
                opacity: 0;
                pointer-events: none;
            }
        }

        @media (max-width: 575.98px) {
            body {
                font-size: 14px;
                line-height: 1.5;
            }

            .container,
            .container-fluid {
                --bs-gutter-x: 1.5rem;
            }

            main :where(h1, .h1) { font-size: clamp(1.45rem, 7vw, 1.8rem); line-height: 1.15; }
            main :where(h2, .h2) { font-size: clamp(1.2rem, 5.5vw, 1.45rem); line-height: 1.2; }
            main :where(h3, .h3, .h4) { font-size: 1.05rem; line-height: 1.25; }

            main .py-5 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
            main .py-4 { padding-top: 1.1rem !important; padding-bottom: 1.1rem !important; }
            main .my-5 { margin-top: 1.5rem !important; margin-bottom: 1.5rem !important; }
            main .my-4 { margin-top: 1.1rem !important; margin-bottom: 1.1rem !important; }
            main .p-5 { padding: 1.25rem !important; }
            main .p-4 { padding: 1rem !important; }
            main .p-3 { padding: 0.75rem !important; }
            main .gap-4 { gap: 0.8rem !important; }
            main .gap-3 { gap: 0.6rem !important; }
            main .mb-5 { margin-bottom: 1.5rem !important; }
            main .mb-4 { margin-bottom: 1rem !important; }
            main .mt-5 { margin-top: 1.5rem !important; }
            main .mt-4 { margin-top: 1rem !important; }

            main :where(.card, [class$="-card"], [class*="-card "]) {
                border-radius: 14px;
            }

            main :where(.btn, button, [role="button"]) {
                max-width: 100%;
            }

            main .btn-lg {
                min-height: 42px;
                padding: 0.5rem 0.85rem;
                font-size: 0.86rem;
            }

            main :where(.form-control, .form-select) {
                min-height: 42px;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            [data-availability-badge] {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            [data-availability-badge] .availability-label-full { display: none; }
            [data-availability-badge] .availability-label-compact { display: inline !important; }

            .modal-dialog {
                width: calc(100vw - 2rem) !important;
                max-width: calc(100vw - 2rem) !important;
                margin: 0.75rem auto !important;
            }

            .modal-content {
                overflow-x: hidden !important;
                overflow-y: auto !important;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            input:not([type="checkbox"]):not([type="radio"]),
            select,
            textarea {
                font-size: 16px !important;
            }

            #flashNotifications {
                top: max(0.75rem, env(safe-area-inset-top)) !important;
                right: 0.75rem !important;
                left: 0.75rem !important;
                width: auto !important;
                max-width: none !important;
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
    <!-- Flash Notifications Container -->
    <div id="flashNotifications" style="position: fixed; top: 80px; right: 20px; z-index: 10000; width: 350px; max-width: calc(100vw - 40px);">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-lg mb-3" role="alert" style="border-radius: 12px; border-left: 4px solid #10b981;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-check-circle-fill me-2 fs-5" style="color: #10b981;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Thành công!</strong>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Đóng"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-lg mb-3" role="alert" style="border-radius: 12px; border-left: 4px solid #ef4444;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5" style="color: #ef4444;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Lỗi!</strong>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Đóng"></button>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show shadow-lg mb-3" role="alert" style="border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-circle-fill me-2 fs-5" style="color: #f59e0b;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Cảnh báo!</strong>
                        {{ session('warning') }}
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Đóng"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show shadow-lg mb-3" role="alert" style="border-radius: 12px; border-left: 4px solid #3b82f6;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill me-2 fs-5" style="color: #3b82f6;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Thông tin</strong>
                        {{ session('info') }}
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Đóng"></button>
                </div>
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-primary alert-dismissible fade show shadow-lg mb-3" role="alert" style="border-radius: 12px; border-left: 4px solid #0d9373;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill me-2 fs-5" style="color: #0d9373;"></i>
                    <div class="flex-grow-1">
                        {{ session('status') }}
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Đóng"></button>
                </div>
            </div>
        @endif
    </div>

    <header class="site-header sticky-top" id="siteHeader">
        <nav class="navbar navbar-expand-xxl container py-2">
            <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-2 fw-bold m-0">
                <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="brand-mark" style="object-fit: contain; padding: 2px;">
                <span class="brand-text">Chill Drink</span>
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#clientNavbar" aria-controls="clientNavbar" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse flex-grow-1" id="clientNavbar">
                <ul class="navbar-nav ms-xxl-4 gap-xxl-1">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">Sản Phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('order-lookup.index') }}" class="nav-link {{ request()->routeIs('order-lookup.*') ? 'active' : '' }}">
                            <span class="d-none d-sm-inline">Tra Cứu Đơn Hàng</span>
                            <span class="d-sm-none">Tra Cứu Đơn</span>
                        </a>
                    </li>
                </ul>

                <div class="nav-actions d-flex flex-wrap align-items-center gap-2 ms-xxl-auto mt-2 mt-xxl-0 {{ (!empty($pendingCheckoutGroup) || (!empty($activeOwnedGroup) && !request()->routeIs('group-orders.show'))) ? 'has-group-action' : '' }}">
                    @if(!empty($pendingCheckoutGroup))
                        @if(!request()->routeIs('checkout.*'))
                        @php
                            $pendingPaymentExpiresAt = ($pendingCheckoutGroup->order?->created_at ?? $pendingCheckoutGroup->locked_at)?->copy()->addMinutes(15);
                        @endphp
                        @if($pendingCheckoutGroup->order && $pendingCheckoutGroup->order->payment_method === 'vnpay' && $pendingCheckoutGroup->order->payment_status !== 'paid')
                            <a href="{{ route('vnpay.payment', $pendingCheckoutGroup->order) }}" class="active-group-return is-checkout" data-pending-payment-link data-expires-at="{{ $pendingPaymentExpiresAt?->toIso8601String() }}" title="Tiếp tục thanh toán đơn nhóm {{ $pendingCheckoutGroup->name }}">
                                <i class="bi bi-credit-card-fill" aria-hidden="true"></i>
                                <span class="active-group-return__label">Tiếp tục thanh toán</span>
                                <span class="active-group-return__time" data-pending-payment-countdown>--:--</span>
                            </a>
                        @elseif((int) session('checkout_group_order_id') === (int) $pendingCheckoutGroup->id)
                            <a href="{{ route('checkout.index') }}" class="active-group-return is-checkout" data-pending-payment-link data-expires-at="{{ $pendingPaymentExpiresAt?->toIso8601String() }}" title="Tiếp tục thanh toán đơn nhóm {{ $pendingCheckoutGroup->name }}">
                                <i class="bi bi-credit-card-fill" aria-hidden="true"></i>
                                <span class="active-group-return__label">Tiếp tục thanh toán</span>
                                <span class="active-group-return__time" data-pending-payment-countdown>--:--</span>
                            </a>
                        @else
                            <form method="POST" action="{{ route('group-orders.pending-checkout.resume') }}" class="m-0">
                                @csrf
                                <button type="submit" class="active-group-return is-checkout" data-pending-payment-link data-expires-at="{{ $pendingPaymentExpiresAt?->toIso8601String() }}" title="Khôi phục và tiếp tục thanh toán đơn nhóm {{ $pendingCheckoutGroup->name }}">
                                    <i class="bi bi-credit-card-fill" aria-hidden="true"></i>
                                    <span class="active-group-return__label">Tiếp tục thanh toán</span>
                                    <span class="active-group-return__time" data-pending-payment-countdown>--:--</span>
                                </button>
                            </form>
                        @endif
                        @endif
                    @elseif(!empty($activeOwnedGroup) && !request()->routeIs('group-orders.show'))
                        <a href="{{ route('group-orders.show', $activeOwnedGroup->code) }}" class="active-group-return" title="Quay lại phòng {{ $activeOwnedGroup->name }}">
                            <i class="bi bi-people-fill" aria-hidden="true"></i>
                            <span class="active-group-return__label">{{ $activeOwnedGroup->owner_id === auth()->id() ? 'Phòng đang mở' : 'Phòng đang tham gia' }}</span>
                            <span class="active-group-return__time" data-active-group-countdown data-closes-at="{{ $activeOwnedGroup->closes_at->toIso8601String() }}">--:--</span>
                            <span class="active-group-return__members" title="{{ $activeOwnedGroup->members_count }} thành viên"><i class="bi bi-person-fill" aria-hidden="true"></i>{{ $activeOwnedGroup->members_count }}</span>
                        </a>
                    @endif
                    @php
                        $headerBranchName = $headerBranch?->name
                            ? (str_starts_with($headerBranch->name, 'Chi nhánh') ? $headerBranch->name : 'Chi nhánh '.$headerBranch->name)
                            : 'Chưa xác định';
                    @endphp
                    @if(($headerBranches ?? collect())->count() > 1)
                        <button
                            type="button"
                            class="header-branch-button"
                            data-bs-toggle="modal"
                            data-bs-target="#branchSwitchModal"
                            data-header-branch
                            data-current-branch-id="{{ $headerBranch?->id }}"
                            aria-label="Đổi chi nhánh, hiện tại là {{ $headerBranchName }}"
                            title="Đổi chi nhánh"
                        >
                            <i class="bi bi-geo-alt-fill header-branch-button__icon" aria-hidden="true"></i>
                            <span class="header-branch-button__copy">
                                <small>Đang mua tại</small>
                                <strong data-header-branch-name>{{ $headerBranchName }}</strong>
                            </span>
                            <i class="bi bi-chevron-down header-branch-button__chevron" aria-hidden="true"></i>
                        </button>
                    @else
                        <span class="header-branch-button" title="Chi nhánh hiện tại" data-header-branch data-current-branch-id="{{ $headerBranch?->id }}">
                            <i class="bi bi-geo-alt-fill header-branch-button__icon" aria-hidden="true"></i>
                            <span class="header-branch-button__copy">
                                <small>Đang mua tại</small>
                                <strong data-header-branch-name>{{ $headerBranchName }}</strong>
                            </span>
                        </span>
                    @endif
                    <form action="{{ route('products.index') }}" method="GET" class="d-flex client-search gap-2" role="search">
                        <div class="position-relative flex-grow-1">
                            <i class="bi bi-search position-absolute" style="left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--c-subtle); font-size: 0.85rem;"></i>
                            <input type="search" name="search" class="form-control" placeholder="Tìm đồ uống..." aria-label="Tìm kiếm sản phẩm" value="{{ request('search') }}" style="padding-left: 2.4rem; border-radius: var(--radius-md);">
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Tìm</button>
                    </form>

                    <a href="{{ auth()->check() ? route('favorites.index') : route('login') }}" class="btn btn-outline-secondary cart-button favorite-nav-button {{ request()->routeIs('favorites.*') ? 'active' : '' }}" aria-label="Món yêu thích" title="Món yêu thích">
                        <i class="bi {{ request()->routeIs('favorites.*') ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                    </a>

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
                    $avatarUrl = $avatar && ! $avatarIsPreset ? asset('storage/' . $avatar) : null;
                    @endphp
                    <div class="dropdown notification-dropdown">
                        <button class="notification-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo đơn hàng" id="clientNotificationButton">
                            <i class="bi bi-bell" style="font-size: 1.05rem;"></i>
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <span class="notification-dot" id="clientNotificationDot" aria-hidden="true"></span>
                            @else
                                <span class="notification-dot d-none" id="clientNotificationDot" aria-hidden="true"></span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-menu">
                            <div class="notification-head">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">Thông báo</div>
                                        <div class="text-secondary" style="font-size: 0.8rem;">Cập nhật đơn hàng của bạn</div>
                                    </div>
                                    <span class="badge rounded-pill {{ Auth::user()->unreadNotifications->count() > 0 ? '' : 'd-none' }}" id="clientNotificationBadge" style="background: var(--c-primary-light); color: var(--c-primary); font-size: 0.7rem;">
                                        {{ Auth::user()->unreadNotifications->count() }} mới
                                    </span>
                                </div>
                                @if(Auth::user()->unreadNotifications->count() > 0)
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary w-100"
                                        id="markAllReadBtn"
                                        style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                    <i class="bi bi-check2-all me-1"></i>Đánh dấu tất cả đã đọc
                                </button>
                                @endif
                            </div>
                            <div class="notification-list" id="clientNotificationList">
                                @forelse(Auth::user()->notifications->take(10) as $notification)
                                    @php
                                        $notificationOrderId = $notification->data['order_id'] ?? null;
                                        $notificationUrl = is_numeric($notificationOrderId)
                                            ? route('orders.index', ['order' => (int) $notificationOrderId])
                                            : route('orders.index');
                                    @endphp
                                    <a href="{{ $notificationUrl }}"
                                       class="notification-item is-clickable {{ is_null($notification->read_at) ? 'unread' : '' }}"
                                       data-notification-id="{{ $notification->id }}"
                                       @if(is_numeric($notificationOrderId)) data-order-id="{{ (int) $notificationOrderId }}" @endif>
                                        <span class="notification-icon">
                                            @include('partials.notification-icon', ['type' => $notification->data['type'] ?? null])
                                        </span>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <div class="fw-semibold" style="font-size: 0.85rem;">
                                                    {{ $notification->data['title'] ?? 'Thông báo' }}
                                                </div>
                                                @if(!empty($notification->data['status_label']))
                                                    <span class="badge rounded-pill" style="background: var(--c-primary-light); color: var(--c-primary); font-size: 0.68rem;">
                                                        {{ $notification->data['status_label'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-secondary" style="font-size: 0.8rem;">
                                                {{ $notification->data['message'] ?? '' }}
                                            </div>
                                            <div class="notification-time mt-1">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-center py-4 text-secondary">
                                        <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                                        <div style="font-size: 0.85rem;">Chưa có thông báo mới</div>
                                    </div>
                                @endforelse
                            </div>
                            <div class="p-3 border-top">
                                <a href="{{ route('orders.index') }}" class="btn btn-primary w-100 btn-sm">Xem đơn hàng</a>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown profile-dropdown text-center">
                        <button class="user-avatar dropdown-toggle {{ $avatarClass }}" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản">
                            @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ Auth::user()->name }}">
                            @else
                            {{ mb_substr(Auth::user()->name, 0, 1) }}
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end profile-menu">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>Đơn hàng của tôi</a></li>
                            <li><a class="dropdown-item" href="{{ route('favorites.index') }}"><i class="bi bi-heart me-2"></i>Món yêu thích</a></li>
                            <li><a class="dropdown-item" href="{{ route('loyalty.index') }}"><i class="bi bi-star-fill me-2 text-warning"></i>Điểm thưởng</a></li>
                            @if(auth()->user()->isStaff())
                                @php
                                    $staffDashboardUrl = match(true) {
                                        auth()->user()->isSuperAdmin() => route('admin.super-admin'),
                                        auth()->user()->isAdmin() => route('admin.dashboard'),
                                        auth()->user()->isCskh() => route('admin.chat.index'),
                                        auth()->user()->isStaffOnly() => route('staff.dashboard'),
                                        auth()->user()->isShipper() => route('shipper.dashboard'),
                                        default => route('admin.dashboard'),
                                    };
                                @endphp
                                <li><a class="dropdown-item" href="{{ $staffDashboardUrl }}"><i class="bi bi-grid-1x2 me-2"></i>Quay lại trang quản lý</a></li>
                            @endif
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
                    <a href="{{ route('login') }}" class="nav-login-button btn btn-outline-primary {{ request()->routeIs('login') ? 'active' : '' }}">Đăng Nhập</a>
                    <a href="{{ route('register') }}" class="nav-register-button btn btn-primary {{ request()->routeIs('register') ? 'active' : '' }}">Đăng Ký</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    @if(($headerBranches ?? collect())->count() > 1)
        <div class="modal fade branch-switch-modal" id="branchSwitchModal" tabindex="-1" aria-labelledby="branchSwitchTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h2 class="modal-title h5 fw-bold mb-1" id="branchSwitchTitle">Chọn chi nhánh khác</h2>
                            <p class="small text-secondary mb-0">Sản phẩm, tồn kho và gợi ý thời tiết sẽ được cập nhật theo chi nhánh bạn chọn.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('branch.select') }}" class="branch-option-list">
                            @csrf
                            @foreach($headerBranches as $availableBranch)
                                @php
                                    $isCurrentBranch = (int) $headerBranch?->id === (int) $availableBranch->id;
                                    $availableBranchName = str_starts_with($availableBranch->name, 'Chi nhánh')
                                        ? $availableBranch->name
                                        : 'Chi nhánh '.$availableBranch->name;
                                @endphp
                                <button
                                    type="submit"
                                    name="branch_id"
                                    value="{{ $availableBranch->id }}"
                                    data-branch-option-id="{{ $availableBranch->id }}"
                                    class="branch-option {{ $isCurrentBranch ? 'is-current' : '' }}"
                                    @disabled($isCurrentBranch)
                                >
                                    <span class="branch-option__icon"><i class="bi bi-shop"></i></span>
                                    <span class="branch-option__copy">
                                        <strong>{{ $availableBranchName }}</strong>
                                        <small>{{ $availableBranch->address ?: 'Địa chỉ đang được cập nhật' }}</small>
                                    </span>
                                    <span class="branch-option__state">
                                        {{ $isCurrentBranch ? 'Đang chọn' : 'Chuyển sang' }}
                                    </span>
                                </button>
                            @endforeach
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <main style="min-height: {{ request()->routeIs('login', 'register', 'password.*', 'verification.*') ? 'calc(100dvh - 80px)' : '100vh' }}; overflow: {{ request()->routeIs('login', 'register', 'password.*', 'verification.*') ? 'hidden' : 'visible' }};">
        @yield('content')
    </main>

    @if(request()->routeIs('products.*') && session('checkout_return_active'))
        @php
            $checkoutReturnQuantity = collect(session('cart', []))
                ->sum(fn ($item) => max(1, (int) ($item['quantity'] ?? 1)));
        @endphp
        <div class="checkout-return-bar" role="region" aria-label="Quay lại thanh toán">
            <div class="checkout-return-bar__copy">
                <strong>Đang chọn thêm đồ uống</strong>
                <span><span data-checkout-return-count>{{ $checkoutReturnQuantity }}</span> món trong giỏ · đơn hàng vẫn được giữ</span>
            </div>
            <a href="{{ route('checkout.index') }}" class="checkout-return-bar__button">
                Thanh toán <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    @endif

    @unless(request()->routeIs('login', 'register', 'password.*', 'verification.*'))
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
                        <a href="{{ route('products.index', ['category' => 'tra-sua']) }}" class="footer-link">Trà sữa</a>
                        <a href="{{ route('products.index', ['category' => 'ca-phe']) }}" class="footer-link">Cà phê</a>
                        <a href="{{ route('products.index', ['category' => 'nuoc-ep']) }}" class="footer-link">Nước ép</a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h3 class="footer-heading">Hỗ trợ</h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('legal.terms') }}" class="footer-link">Liên hệ & Hỗ trợ</a>
                        <a href="{{ route('legal.terms') }}" class="footer-link">Điều khoản dịch vụ</a>
                        <a href="{{ route('legal.returns') }}" class="footer-link">Chính sách đổi trả</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h3 class="footer-heading">Liên hệ</h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="tel:0945606336" class="footer-link text-decoration-none">
                            <i class="bi bi-telephone me-2"></i>0945 606 336
                        </a>
                        <a href="mailto:contact@chilldrink.com" class="footer-link text-decoration-none">
                            <i class="bi bi-envelope me-2"></i>contact@chilldrink.com
                        </a>
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
    @endunless

    @php
        $isGroupOrderMember = request()->routeIs('group-orders.show')
            && isset($currentMember)
            && $currentMember;
    @endphp
    @if(!auth()->check() || auth()->user()?->isCustomer() || $isGroupOrderMember)
        @if(! View::hasSection('hide-client-chatbox'))
            @include('components.chatbox')
        @endif
    @endif

    @auth
        @if(auth()->user()->isCustomer())
            {{-- Banner xin quyền vị trí -- chỉ hiện khi permission chưa được cấp --}}
            <div
                id="location-permission-banner"
                style="display:none; position:fixed; top:1rem; left:50%; transform:translateX(-50%); z-index:1100;
                       background:#fff; border:1px solid #d1fae5; border-radius:16px; box-shadow:0 8px 32px rgba(7,52,58,.18);
                       padding:1rem 1.25rem; gap:.75rem; align-items:flex-start; max-width:360px; width:calc(100vw - 2rem);"
            >
                <span style="font-size:1.5rem; flex-shrink:0;">📍</span>
                <div style="flex:1;">
                    <p style="margin:0 0 .3rem; font-weight:700; font-size:.9rem; color:#065f46;">Cho phép truy cập vị trí?</p>
                    <p style="margin:0 0 .75rem; font-size:.8rem; color:#374151; line-height:1.5;">
                        Chill Drink sẽ tìm chi nhánh gần bạn nhất để hỗ trợ đặt hàng và chat nhanh hơn.
                    </p>
                    <div style="display:flex; gap:.5rem;">
                        <button id="location-allow-btn"
                            style="flex:1; padding:.45rem .75rem; border-radius:8px; border:0; font-size:.8rem; font-weight:700;
                                   background:linear-gradient(135deg,var(--c-primary),var(--c-accent)); color:#fff; cursor:pointer;">
                            Cho phép
                        </button>
                        <button id="location-skip-btn"
                            style="padding:.45rem .75rem; border-radius:8px; border:1px solid #d1d5db; font-size:.8rem;
                                   background:#f9fafb; color:#6b7280; cursor:pointer;">
                            Bỏ qua
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endauth

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

            const isQuantityForm = form.matches('[data-cart-qty-form]');
            const quantityInput = isQuantityForm ? form.querySelector('[data-cart-qty-input]') : null;
            const currentQuantityValue = quantityInput ? String(quantityInput.value || '').trim() : '';

            if (isQuantityForm && form._cartQtySyncing) {
                event.preventDefault();
                form._cartQtyPending = currentQuantityValue;
                return;
            }

            event.preventDefault();

            const submitter = event.submitter;
            const formData = new FormData(form);
            const isAddAction = form.action.includes('/cart/add/');
            const originalSubmitterHtml = submitter?.innerHTML;

            if (isQuantityForm) {
                form._cartQtySyncing = true;
                form._cartQtyPending = null;
                form._cartQtyLastSent = currentQuantityValue;
            }

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

                const isIconButton = submitter.classList.contains('add-round') || submitter.classList.contains('product-cart-btn') || submitter.getAttribute('aria-label') === 'Thêm vào giỏ';
                const hasDynamicContent = submitter.classList.contains('drink-customizer__submit');
                const hasText = submitter.textContent.trim().length > 0 && !isIconButton;

                if (state === 'loading' && isIconButton) {
                    submitter.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
                }

                if (state === 'success' && isIconButton) {
                    submitter.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
                }

                if (state === 'loading' && hasText && !hasDynamicContent) {
                    submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang thêm';
                }

                if (state === 'success' && hasText && !hasDynamicContent) {
                    submitter.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Đã thêm';
                }

                if (state === 'idle') {
                    if (!hasDynamicContent && typeof originalSubmitterHtml === 'string') {
                        submitter.innerHTML = originalSubmitterHtml;
                    }
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

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                })[char]);
            }

            function showCartFeedback(message, options = {}) {
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

                const isWarning = options.type === 'warning';
                const icon = isWarning ? 'bi-exclamation-triangle' : 'bi-bag-check';
                const redirectUrl = !isWarning
                    ? (options.redirectUrl || '{{ route('cart.index', [], false) }}')
                    : (options.redirectUrl || '');
                const action = options.redirectUrl
                    ? `<a class="btn btn-primary rounded-pill fw-bold cart-feedback-action" href="${escapeHtml(options.redirectUrl)}">${escapeHtml(options.redirectLabel || 'Mở')}</a>`
                    : '';

                const hideCartFeedback = () => {
                    feedback.classList.remove('show');
                    document.body.classList.remove('cart-warning-open');
                };

                feedback.classList.toggle('is-warning', isWarning);
                feedback.setAttribute('role', isWarning ? 'alertdialog' : 'status');
                feedback.classList.toggle('is-clickable', Boolean(redirectUrl));
                feedback.innerHTML = `
                    <span class="cart-feedback-icon"><i class="bi ${icon}"></i></span>
                    <span class="cart-feedback-copy">
                        <strong>${isWarning ? 'Bạn đang có đơn nhóm' : 'Thêm món thành công'}</strong>
                        <span>${escapeHtml(message || 'Đã thêm vào giỏ hàng')}</span>
                    </span>
                    <span class="cart-feedback-actions">
                        ${action}
                        <button type="button" class="cart-feedback-close" aria-label="Đóng">${isWarning ? 'Để sau' : '<i class="bi bi-x-lg"></i>'}</button>
                    </span>
                `;
                feedback.onclick = (event) => {
                    if (!redirectUrl) {
                        return;
                    }

                    if (event.target.closest('button, a, input, select, textarea')) {
                        return;
                    }

                    window.location.href = redirectUrl;
                };
                feedback.style.cursor = redirectUrl ? 'pointer' : '';
                feedback.querySelector('.cart-feedback-close')?.addEventListener('click', hideCartFeedback);
                document.body.classList.toggle('cart-warning-open', isWarning);
                feedback.classList.add('show');

                window.clearTimeout(feedback._hideTimer);
                if (!isWarning) {
                    feedback._hideTimer = window.setTimeout(hideCartFeedback, 1800);
                }
            }

            if (submitter && submitter.name) {
                formData.set(submitter.name, submitter.value);
            }

            if (submitter) {
                submitter.disabled = true;
            }

            setAddButtonState('loading');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin',
                    body: formData
                });

                if (!response.ok) {
                    let errorData = {};

                    try {
                        errorData = await response.json();
                    } catch (error) {
                        errorData = {};
                    }

                    const fallbackMessage = response.status === 419
                        ? 'Phiên đăng nhập hoặc CSRF token đã hết hạn. Hãy tải lại trang rồi thử lại.'
                        : 'Không thể thêm sản phẩm vào giỏ hàng.';

                    showCartFeedback(errorData.message || fallbackMessage, {
                        type: 'warning',
                        redirectUrl: errorData.redirect_url,
                        redirectLabel: errorData.redirect_label,
                    });

                    return;
                }

                const data = await response.json();
                const badges = document.querySelectorAll('[data-cart-badge]');

                badges.forEach((badge) => {
                    badge.textContent = data.count;
                    badge.classList.toggle('d-none', data.count < 1);
                });

                document.querySelectorAll('[data-checkout-return-count]').forEach((count) => {
                    count.textContent = data.quantity_count ?? data.count;
                });

                if (isAddAction) {
                    setAddButtonState('success');
                    flyToCart();
                    if (document.body.dataset.page === 'cart') {
                        sessionStorage.setItem('cart_flash_toast', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
                        window.setTimeout(() => {
                            window.location.reload();
                        }, 400);
                        return;
                    }
                    showCartFeedback(data.message, {
                        redirectUrl: '{{ route('cart.index', [], false) }}',
                        redirectLabel: 'Xem giỏ hàng',
                    });
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
                            const minusButton = qtyForm?.querySelector('[data-cart-qty-minus], button[aria-label^="Giảm"]');
                            const plusButton = qtyForm?.querySelector('[data-cart-qty-plus], button[aria-label^="Tăng"]');

                            if (minusButton) {
                                if ('value' in minusButton) {
                                    minusButton.value = Math.max(1, item.quantity - 1);
                                }
                                minusButton.disabled = item.quantity <= 1;
                            }

                            if (plusButton) {
                                if ('value' in plusButton) {
                                    plusButton.value = item.quantity + 1;
                                }
                                plusButton.disabled = item.quantity >= 99;
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
                } else {
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

                if (isQuantityForm) {
                    form._cartQtySyncing = false;

                    const pendingQuantity = form._cartQtyPending;
                    form._cartQtyPending = null;

                    if (pendingQuantity && pendingQuantity !== form._cartQtyLastSent) {
                        if (quantityInput) {
                            quantityInput.value = pendingQuantity;
                        }

                        window.setTimeout(() => {
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                                return;
                            }

                            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                        }, 0);
                    }
                }
            }
        });
    </script>

    <!-- Auto-dismiss flash notifications with animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationContainer = document.getElementById('flashNotifications');
            if (!notificationContainer) return;

            const alerts = notificationContainer.querySelectorAll('.alert');

            alerts.forEach(function(alert) {
                // Giữ đủ lâu để người dùng đọc và thao tác nếu thông báo có hướng dẫn.
                const dismissTimer = setTimeout(function() {
                    // Add fade-out animation
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(100%)';

                    // Remove from DOM after animation
                    setTimeout(function() {
                        if (alert.parentNode) {
                            const bsAlert = bootstrap.Alert.getInstance(alert);
                            if (bsAlert) {
                                bsAlert.close();
                            } else {
                                alert.remove();
                            }
                        }
                    }, 500);
                }, 15000);

                // Clear timer if manually closed
                alert.addEventListener('closed.bs.alert', function() {
                    clearTimeout(dismissTimer);
                });
            });
        });
    </script>
    <script>
        (() => {
            let branchSyncSequence = 0;

            window.syncStorefrontBranch = async function (branchId, branchName = '') {
                const normalizedId = String(branchId || '');
                const headerBranch = document.querySelector('[data-header-branch]');

                if (!normalizedId || headerBranch?.dataset.currentBranchId === normalizedId) {
                    return;
                }

                const sequence = ++branchSyncSequence;
                const response = await fetch(@json(route('branch.select', [], false)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ branch_id: normalizedId }),
                });
                const payload = await response.json();

                if (sequence !== branchSyncSequence || !response.ok || !payload.success) {
                    return;
                }

                const displayName = payload.branch?.display_name || branchName || 'Chi nhánh đã chọn';
                document.querySelectorAll('[data-header-branch]').forEach((element) => {
                    element.dataset.currentBranchId = normalizedId;
                    element.setAttribute('aria-label', `Đổi chi nhánh, hiện tại là ${displayName}`);
                    const nameElement = element.querySelector('[data-header-branch-name]');
                    if (nameElement) nameElement.textContent = displayName;
                });
                document.querySelectorAll('[data-branch-option-id]').forEach((option) => {
                    const isCurrent = option.dataset.branchOptionId === normalizedId;
                    option.disabled = isCurrent;
                    option.classList.toggle('is-current', isCurrent);
                    const state = option.querySelector('.branch-option__state');
                    if (state) state.textContent = isCurrent ? 'Đang chọn' : 'Chuyển sang';
                });
            };
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentLink = document.querySelector('[data-pending-payment-link]');
            const paymentTimer = paymentLink?.querySelector('[data-pending-payment-countdown]');

            if (paymentLink && paymentTimer) {
                const expiresAt = new Date(paymentLink.dataset.expiresAt).getTime();
                const paymentWrapper = paymentLink.tagName === 'BUTTON' ? paymentLink.closest('form') : paymentLink;
                let paymentTimerId = null;

                const stopPaymentTimer = function () {
                    if (paymentTimerId === null) return;
                    window.clearInterval(paymentTimerId);
                    paymentTimerId = null;
                };
                const tickPayment = function () {
                    const seconds = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
                    paymentTimer.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
                    if (seconds === 0) {
                        paymentWrapper?.remove();
                        stopPaymentTimer();
                    }
                };
                const startPaymentTimer = function () {
                    if (document.hidden || paymentTimerId !== null || !paymentWrapper?.isConnected) return;
                    tickPayment();
                    paymentTimerId = window.setInterval(tickPayment, 1000);
                };

                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) stopPaymentTimer();
                    else startPaymentTimer();
                });
                window.addEventListener('pagehide', stopPaymentTimer, { once: true });
                startPaymentTimer();
            }

            const activeGroupTimer = document.querySelector('[data-active-group-countdown]');
            if (!activeGroupTimer) return;

            const link = activeGroupTimer.closest('.active-group-return');
            const closesAt = new Date(activeGroupTimer.dataset.closesAt).getTime();
            let timerId = null;
            const tick = function () {
                const seconds = Math.max(0, Math.ceil((closesAt - Date.now()) / 1000));
                const minutes = Math.floor(seconds / 60);
                const hours = Math.floor(minutes / 60);
                activeGroupTimer.textContent = hours > 0
                    ? String(hours).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0')
                    : String(minutes).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
                if (seconds === 0) {
                    link?.remove();
                    stopTimer();
                }
            };
            const startTimer = function () {
                if (document.hidden || timerId !== null || !link?.isConnected) return;
                tick();
                timerId = window.setInterval(tick, 1000);
            };
            const stopTimer = function () {
                if (timerId === null) return;
                window.clearInterval(timerId);
                timerId = null;
            };
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) stopTimer();
                else startTimer();
            });
            window.addEventListener('pagehide', stopTimer, { once: true });
            startTimer();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @auth
            // Chỉ chạy khi user đã đăng nhập
            if (!navigator.geolocation) return;
            const hasManualBranchSelection = @json(session('branch_selection_mode') === 'manual');
            if (hasManualBranchSelection) return;
            const preservePageStateDuringGeoUpdate = @json(request()->routeIs('checkout.*', 'cart.index'));

            if (preservePageStateDuringGeoUpdate) {
                return;
            }

            // Hàm gửi tọa độ lên server
            function submitLocation(lat, lng) {
                fetch('{{ route('select-nearest-branch', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.changed) {
                        window.location.reload();
                    }
                })
                .catch(err => console.error("Lỗi xác định vị trí chi nhánh:", err));
            }

            // Kiểm tra trạng thái quyền vị trí
            if (navigator.permissions) {
                navigator.permissions.query({ name: 'geolocation' }).then(function(result) {
                    if (result.state === 'granted') {
                        // Đã cấp quyền trước đó → lấy ngay, không hỏi nữa
                        navigator.geolocation.getCurrentPosition(
                            pos => submitLocation(pos.coords.latitude, pos.coords.longitude),
                            err => console.warn("Lỗi GPS:", err),
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
                        );
                    } else if (result.state === 'prompt') {
                        // Chưa hỏi → hiện banner giải thích trước khi hỏi
                        var banner = document.getElementById('location-permission-banner');
                        if (banner) banner.style.display = 'flex';
                    }
                    // 'denied' → không làm gì
                });
            } else {
                // Browser không hỗ trợ Permissions API → thử lấy thẳng
                navigator.geolocation.getCurrentPosition(
                    pos => submitLocation(pos.coords.latitude, pos.coords.longitude),
                    err => console.warn("Lỗi GPS:", err),
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
                );
            }


            // Nút "Cho phép" trên banner
            var allowBtn = document.getElementById('location-allow-btn');
            if (allowBtn) {
                allowBtn.addEventListener('click', function() {
                    var banner = document.getElementById('location-permission-banner');
                    if (banner) banner.style.display = 'none';
                    navigator.geolocation.getCurrentPosition(
                        pos => submitLocation(pos.coords.latitude, pos.coords.longitude),
                        function(err) { console.warn("User từ chối vị trí:", err); },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                });
            }

            // Nút "Bỏ qua"
            var skipBtn = document.getElementById('location-skip-btn');
            if (skipBtn) {
                skipBtn.addEventListener('click', function() {
                    var banner = document.getElementById('location-permission-banner');
                    if (banner) banner.style.display = 'none';
                });
            }
            @endauth
        });
    </script>
    @include('partials.realtime')
    @include('partials.client-notifications')
    @include('partials.instant-actions')
    @stack('scripts')
</body>

</html>
