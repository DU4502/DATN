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

html {
    overflow-y: scroll;
    scrollbar-gutter: stable;
}

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
    contain: paint;
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
