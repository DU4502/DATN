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

        /* ─── Alias mappings for --admin- variables used in views ─── */
        --admin-primary: var(--a-primary);
        --admin-primary-dark: var(--a-primary-dark);
        --admin-primary-light: var(--a-primary-light);
        --admin-border: var(--a-border);
        --admin-border-light: var(--a-border-light);
        --admin-danger: var(--a-danger);
        --admin-warning: var(--a-warning);
        --admin-success: var(--a-success);
        --admin-muted: var(--a-muted);
        --admin-soft-2: var(--a-bg-subtle);
    }

    /* ─── Base ─── */
    *, *::before, *::after { box-sizing: border-box; }

html {
    width: 100%;
    max-width: 100%;
    overflow-x: clip;
    overflow-y: scroll;
    scrollbar-gutter: stable;
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
}

body {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    overflow-x: clip;
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

img, svg, video, canvas { max-width: 100%; }

.admin-shell,
.admin-content,
.admin-page,
.admin-topbar,
.admin-card,
.row,
[class*="col-"] { min-width: 0; }

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
.modal-content { max-height: calc(100dvh - 1.5rem); overflow-x: hidden; overflow-y: auto; overscroll-behavior: contain; }
.modal-body { min-width: 0; overflow-y: auto; overscroll-behavior: contain; }
.modal-footer { flex-wrap: wrap; }
.dropdown-menu { max-width: calc(100vw - 1.5rem); }

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
        width: 48px; height: 48px;
        border-radius: var(--radius-md);
        background: var(--a-surface);
        border: 1.5px solid var(--a-border);
        box-shadow: var(--shadow-sm);
        font-size: 1rem; font-weight: 800;
    }

    .admin-logo-title {
        margin: 0; font-size: 1.15rem;
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

    .admin-mobile-toggle,
    .admin-sidebar-close,
    .admin-sidebar-backdrop {
        display: none;
    }

    /* ─── Content ─── */
    .admin-content { min-width: 0; }

    .admin-topbar {
        position: sticky; top: 0; z-index: 40;
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px;
        padding: 12px 28px;
        background: #f8fafb;
        border-bottom: 1px solid var(--a-border);
        transform: translateZ(0);
        backface-visibility: hidden;
        overflow: visible;
        isolation: isolate;
    }

    .admin-topbar-actions {
        display: flex; align-items: center;
        gap: 10px; flex: 0 0 auto; white-space: nowrap;
        position: relative;
        z-index: 2;
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
        cursor: pointer;
        box-shadow: 0 0 0 2px #fff, 0 0 0 3px var(--a-border);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .admin-avatar:hover, .admin-avatar[aria-expanded="true"] {
        transform: translateY(-1px);
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--a-primary-glow);
    }

    .admin-avatar:focus-visible {
        outline: 3px solid var(--a-primary-glow);
        outline-offset: 3px;
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

    .admin-table-card, .admin-table-card .table-responsive {
        overflow: visible;
    }

    .admin-sticky-tools {
        position: sticky;
        top: 61px;
        z-index: 35;
        margin: -28px -28px 1.5rem;
        padding: 18px 28px;
        background: rgba(248, 250, 251, 0.94);
        border-bottom: 1px solid var(--a-border);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        overflow: visible;
    }

    .admin-filter-panel {
        position: relative;
        z-index: 36;
        margin-bottom: 1.5rem;
    }

    .admin-filter-panel.d-none {
        display: none !important;
    }

    .admin-category-scroller {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.5rem;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 0.25rem;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }

    .admin-category-scroller::-webkit-scrollbar {
        height: 6px;
    }

    .admin-category-scroller::-webkit-scrollbar-thumb {
        background: var(--a-border);
        border-radius: var(--radius-full);
    }

    .admin-category-scroller .btn {
        flex: 0 0 auto;
        white-space: nowrap;
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

    .pagination {
        align-items: center;
        gap: 0.3rem;
        margin-bottom: 0;
    }

    .pagination .page-link {
        min-width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-full) !important;
        border: 1.5px solid var(--a-border);
        color: var(--a-primary);
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
        background: var(--a-primary);
        border-color: var(--a-primary);
    }

    .pagination .page-item.disabled .page-link {
        color: var(--a-subtle);
        background: var(--a-bg-subtle);
        border-color: var(--a-border-light);
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

    .admin-gallery-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .admin-gallery-preview img {
        width: 72px;
        height: 72px;
        border: 1px solid var(--a-border);
        border-radius: var(--radius-sm);
        object-fit: contain;
        object-position: center;
        padding: 0.18rem;
        background: #fff;
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

    .badge-soft-info {
        color: #1D4ED8; background: #DBEAFE;
    }

    .text-primary { color: var(--a-primary) !important; }

    /* ─── Responsive ─── */
@media (max-width: 991.98px) {
        .admin-shell { padding-left: 0; }
        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1050;
            width: min(300px, calc(100vw - 2.5rem));
            height: 100dvh;
            padding: 14px 12px;
            overflow-x: hidden;
            overflow-y: auto;
            border-right: 1px solid var(--a-border);
            border-bottom: 0;
            box-shadow: 18px 0 45px rgba(15, 23, 42, 0.18);
            transform: translateX(-105%);
            transition: transform 0.22s ease;
        }
        .admin-sidebar.open {
            transform: translateX(0);
        }
        .admin-sidebar .admin-logo {
            padding-right: 42px;
        }
        .admin-sidebar .nav {
            flex-direction: column !important;
            overflow: visible;
            gap: 2px;
        }
        .admin-sidebar .nav-link { white-space: normal; }
        .admin-mobile-toggle,
        .admin-sidebar-close {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
            padding: 0;
            border: 1px solid var(--a-border);
            border-radius: 10px;
            background: var(--a-surface);
            color: var(--a-ink);
        }
        .admin-sidebar-close {
            position: absolute;
            top: 14px;
            right: 12px;
            z-index: 2;
        }
        .admin-sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
        }
        .admin-sidebar-backdrop.show { display: block; }
        body.admin-sidebar-open { overflow: hidden; }
        .admin-topbar {
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
        }
        .admin-topbar > .d-flex:first-child {
            min-width: 0;
            flex: 1 1 240px;
        }
        .admin-sticky-tools {
            position: sticky;
            top: 0;
            margin: -20px -20px 1.25rem;
            padding: 14px 20px;
            z-index: 35;
        }
        .admin-search { width: 100%; }
        .admin-page { padding: 20px; }
    .admin-table-card .table-responsive {
        overflow-x: auto; overflow-y: visible;
    }
}

@media (max-width: 575.98px) {
    body { font-size: 13px; line-height: 1.5; }
    h1, .h1 { font-size: 1.45rem; }
    h2, .h2 { font-size: 1.2rem; }
    h3, .h3, .h4 { font-size: 1rem; }
    .admin-sidebar { padding: 10px 8px; }
    .admin-logo { padding: 0 42px 12px 8px; }
    .admin-logo-mark { width: 42px; height: 42px; }
    .admin-sidebar .nav {
        margin-inline: 0;
        padding: 0 0 6px;
    }
    .admin-sidebar .nav-link {
        padding: 8px 10px;
    }
    .admin-topbar { padding: 12px; gap: 10px; }
    .admin-topbar > * { min-width: 0; }
    .admin-topbar > .d-flex:first-child {
        display: contents !important;
    }
    .admin-topbar {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) auto;
        align-items: center;
    }
    .admin-topbar .admin-mobile-toggle {
        grid-column: 1;
        grid-row: 1;
    }
    .admin-topbar > .d-flex:first-child > h1 {
        grid-column: 2;
        grid-row: 1;
        align-self: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .admin-topbar > .d-flex:first-child > .admin-search,
    .admin-topbar > .d-flex:first-child > a {
        grid-column: 1 / -1;
        grid-row: 2;
    }
    .admin-topbar-actions {
        grid-column: 3;
        grid-row: 1;
        width: auto;
        margin-left: auto;
        min-width: 0;
        flex-wrap: nowrap;
        justify-content: flex-end;
        white-space: normal;
    }
    .admin-sticky-tools {
        margin: -12px -12px 1rem;
        padding: 10px 12px;
    }
    .admin-page { padding: 12px 12px 28px; }
    .admin-card { border-radius: 12px; }
    .admin-card.p-4, .admin-card.p-5 { padding: 0.9rem !important; }
    .admin-page .row.g-4 { --bs-gutter-x: 0.75rem; --bs-gutter-y: 0.75rem; }
    .admin-page .row.g-3 { --bs-gutter-x: 0.6rem; --bs-gutter-y: 0.6rem; }
    .admin-page .mb-4 { margin-bottom: 0.9rem !important; }
    .admin-page .mb-5 { margin-bottom: 1.25rem !important; }
    .admin-page .btn-lg { min-height: 40px; padding: 0.45rem 0.75rem; font-size: 0.82rem; }
    .admin-empty-state { padding: 1.25rem 0.9rem; }
    .modal-dialog { width: calc(100vw - 2rem) !important; max-width: calc(100vw - 2rem) !important; margin: 0.75rem auto !important; }
    .modal-content { overflow-x: hidden !important; overflow-y: auto !important; }
    .modal-header, .modal-body, .modal-footer { padding-left: 1rem; padding-right: 1rem; }
    input:not([type="checkbox"]):not([type="radio"]), select, textarea { font-size: 16px !important; }
}

    /* Không animate toàn bộ trang: tránh nhấp nháy khi Chrome dựng lại lớp sticky. */
</style>
