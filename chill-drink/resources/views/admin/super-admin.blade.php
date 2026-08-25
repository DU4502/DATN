@extends('layouts.super-admin')

@section('page-title', 'Tổng quan')

@section('content')
<style>
    .sa-page {
        --sa-green: #0d9373;
        --sa-green-dark: #067a5f;
        --sa-green-soft: #e7f7f2;
        --sa-ink: #111827;
        --sa-muted: #6b7280;
        --sa-border: #e1e6e4;
        --sa-card-radius: 16px;
        --sa-card-padding: 1.35rem;
        --sa-section-gap: 1.45rem;
        --sa-control-height: 48px;
        --sa-table-row-height: 58px;
        --sa-font-body: 16px;
        --sa-font-small: 0.84rem;
        --sa-font-menu: 0.96rem;
        --sa-font-heading: 1.26rem;
        font-size: var(--sa-font-body);
        line-height: 1.55;
        display: grid;
        gap: var(--sa-section-gap);
    }

    .sa-header, .sa-panel-header, .sa-stat-top, .sa-health-row, .sa-security-item,
    .sa-admin-cell, .sa-actions, .sa-pagination, .sa-chart-meta {
        display: flex;
        align-items: center;
    }

    .sa-header {
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem 1.25rem;
        flex-wrap: nowrap;
        margin-bottom: 0.2rem;
    }
    .sa-header-copy {
        flex: 1 1 0;
        min-width: 0;
        max-width: 600px;
        display: grid;
        gap: 0.12rem;
        padding-top: 0.05rem;
    }
    .sa-kicker { margin: 0; color: var(--sa-green); font-size: 0.76rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.02em; line-height: 1.2; }
    .sa-title { margin: 0; color: var(--sa-ink); font-size: clamp(1.5rem, 1.6vw, 1.95rem); font-weight: 900; letter-spacing: -0.035em; line-height: 1.02; }
    .sa-subtitle {
        margin: 0.16rem 0 0;
        color: var(--sa-muted);
        font-size: 0.8rem;
        line-height: 1.32;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        max-width: 62ch;
    }

    .sa-btn {
        min-height: 44px;
        border: 1px solid var(--sa-border);
        border-radius: 12px;
        padding: 0.62rem 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        background: #fff;
        color: var(--sa-ink);
        font-size: 0.86rem;
        font-weight: 750;
        text-decoration: none;
        white-space: nowrap;
    }

    .sa-btn:hover { border-color: var(--sa-green); color: var(--sa-green-dark); }
    .sa-btn-primary { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .sa-btn-primary:hover { background: var(--sa-green-dark); color: #fff; }

    .sa-alert { margin: 0; border-radius: 10px; font-size: 0.84rem; }

    .sa-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem; }
    .sa-stat { min-height: 124px; padding: 0.9rem 0.92rem 0.85rem; border: 1px solid var(--sa-border); border-radius: var(--sa-card-radius); background: #fff; }
    .sa-stat-top { justify-content: space-between; gap: 0.7rem; }
    .sa-stat-icon { width: 40px; height: 40px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green); font-size: 1rem; }
    .sa-stat-note { color: var(--sa-muted); font-size: 0.72rem; font-weight: 700; }
    .sa-stat-value { margin-top: 0.58rem; color: var(--sa-ink); font-size: 1.55rem; font-weight: 900; line-height: 1.03; letter-spacing: -0.03em; }
    .sa-stat-label { margin-top: 0.22rem; color: var(--sa-muted); font-size: 0.8rem; font-weight: 700; }

    .sa-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.1rem; }
    .sa-grid-main { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(320px, 0.72fr); gap: 1.1rem; align-items: start; }
    .sa-panel { border: 1px solid var(--sa-border); border-radius: var(--sa-card-radius); background: #fff; overflow: hidden; scroll-margin-top: 96px; box-shadow: 0 16px 36px rgba(17, 24, 39, 0.04); }
    .sa-panel-header { min-height: 58px; padding: 0.75rem 0.9rem; border-bottom: 1px solid var(--sa-border); justify-content: space-between; gap: 0.7rem; }
    .sa-panel-title { margin: 0; color: var(--sa-ink); font-size: 0.92rem; font-weight: 900; line-height: 1.22; }
    .sa-panel-note { margin: 0.12rem 0 0; color: var(--sa-muted); font-size: 0.74rem; line-height: 1.35; }

    .sa-chart { height: 294px; padding: 1.1rem; display: flex; flex-direction: column; }
    .sa-chart-meta { justify-content: space-between; margin-bottom: 0.9rem; color: var(--sa-muted); font-size: 0.88rem; }
    .sa-chart-bars { min-height: 0; flex: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); align-items: end; gap: 0.8rem; border-bottom: 1px solid var(--sa-border); }
    .sa-chart-bars.six { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .sa-chart-column { height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 0.35rem; }
    .sa-chart-value { min-height: 20px; color: var(--sa-muted); font-size: 0.82rem; font-weight: 700; white-space: nowrap; }
    .sa-chart-bar { width: min(38px, 72%); min-height: 4px; border-radius: 4px 4px 0 0; background: var(--sa-green); }
    .sa-chart-bar.alt { background: #4f8fd9; }
    .sa-chart-label { padding-top: 0.45rem; color: var(--sa-muted); font-size: 0.74rem; font-weight: 700; }

    .sa-time-matrix-panel { overflow: hidden; }
    .sa-time-matrix-header { min-height: 0; align-items: flex-start; gap: 0.75rem; }
    .sa-time-matrix-header-copy { display: grid; gap: 0.18rem; }
    .sa-time-matrix-meta { display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: flex-end; }
    .sa-time-matrix-meta .sa-state { padding: 0.26rem 0.52rem; font-size: 0.68rem; line-height: 1.15; }
    .sa-time-matrix-toolbar { padding: 0.95rem 1rem; display: grid; grid-template-columns: minmax(160px, 1.1fr) minmax(220px, 1fr) minmax(190px, 0.9fr) minmax(140px, 0.6fr) auto; gap: 0.75rem; align-items: end; border-bottom: 1px solid var(--sa-border); background: linear-gradient(180deg, #ffffff, #fbfffd); }
    .sa-time-matrix-field { min-width: 0; display: grid; gap: 0.35rem; }
    .sa-time-matrix-label { margin: 0; color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; }
    .sa-time-matrix-control { width: 100%; }
    .sa-time-matrix-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .sa-time-matrix-pill { min-height: 40px; padding: 0.5rem 0.78rem; border: 1px solid var(--sa-border); border-radius: 999px; background: #fff; color: var(--sa-muted); font-size: 0.8rem; font-weight: 800; }
    .sa-time-matrix-pill.active { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .sa-time-matrix-export { position: relative; align-self: end; }
    .sa-time-matrix-export details { position: relative; }
    .sa-time-matrix-export summary { list-style: none; }
    .sa-time-matrix-export summary::-webkit-details-marker { display: none; }
    .sa-time-matrix-export-menu { position: absolute; right: 0; top: calc(100% + 0.45rem); z-index: 20; min-width: 170px; padding: 0.35rem; border: 1px solid var(--sa-border); border-radius: 14px; background: #fff; box-shadow: 0 14px 34px rgba(17, 24, 39, 0.12); display: none; }
    .sa-time-matrix-export[open] .sa-time-matrix-export-menu { display: grid; }
    .sa-time-matrix-export-item { min-height: 40px; padding: 0.48rem 0.72rem; border-radius: 10px; color: var(--sa-ink); font-size: 0.82rem; font-weight: 750; text-decoration: none; display: flex; align-items: center; }
    .sa-time-matrix-export-item:hover { background: var(--sa-green-soft); color: var(--sa-green-dark); }
    .sa-time-matrix-body { padding: 0.95rem 1rem 1rem; display: grid; gap: 0.75rem; }
    .sa-time-matrix-summary { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; }
    .sa-time-matrix-summary-text { color: var(--sa-muted); font-size: 0.76rem; font-weight: 700; }
    .sa-time-matrix-summary-meta { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
    .sa-time-matrix-summary-meta .sa-state { padding: 0.25rem 0.5rem; font-size: 0.68rem; }
    .sa-time-matrix-table-wrap { overflow-x: auto; border: 1px solid var(--sa-border); border-radius: 0; background: #fff; box-shadow: none; }
    .sa-time-matrix-table { width: max(100%, 1160px); border-collapse: separate; border-spacing: 0; table-layout: auto; }
    .sa-time-matrix-table th,
    .sa-time-matrix-table td { padding: 0.68rem 0.76rem; border-bottom: 1px solid #edf1ef; border-right: 1px solid rgba(148, 163, 184, 0.08); color: #374151; font-size: 0.82rem; vertical-align: middle; text-align: center; }
    .sa-time-matrix-table thead th { position: sticky; top: 0; z-index: 5; background: #f8faf9; color: var(--sa-muted); font-weight: 850; text-align: center; white-space: nowrap; border-bottom: 1px solid #e6ede8; }
    .sa-time-matrix-table thead tr:first-child th { padding-top: 0.82rem; padding-bottom: 0.56rem; vertical-align: bottom; }
    .sa-time-matrix-table thead tr:first-child th.sa-time-matrix-period-group { background: #fbfdfc; color: #334155; font-size: 0.74rem; font-weight: 900; letter-spacing: 0.01em; border-left: 1px solid rgba(148, 163, 184, 0.1); border-right: 1px solid rgba(148, 163, 184, 0.1); border-top: 1px solid rgba(148, 163, 184, 0.08); border-radius: 0; }
    .sa-time-matrix-table thead tr:first-child th.sa-time-matrix-period-group span { display: block; line-height: 1.1; }
    .sa-time-matrix-table thead tr:first-child th.sa-time-matrix-period-group small { display: inline-flex; align-items: center; justify-content: center; margin-top: 0.32rem; padding: 0.14rem 0.42rem; border-radius: 999px; background: #ecfdf5; color: var(--sa-green-dark); font-size: 0.61rem; font-weight: 900; letter-spacing: 0; }
    .sa-time-matrix-table thead tr:first-child th.sa-time-matrix-period-single { background: #fbfcfd; }
    .sa-time-matrix-table thead tr:nth-child(2) th { top: 44px; z-index: 4; padding-top: 0.52rem; padding-bottom: 0.52rem; font-size: 0.69rem; letter-spacing: 0.01em; }
    .sa-time-matrix-subhead { min-width: 76px; }
    .sa-time-matrix-subhead-revenue { color: var(--sa-green-dark); background: #f3fbf7 !important; }
    .sa-time-matrix-subhead-orders { color: #475569; background: #fafbfc !important; }
    .sa-time-matrix-table thead th small { display: block; margin-top: 0.15rem; color: var(--sa-green); font-size: 0.66rem; font-weight: 800; }
    .sa-time-matrix-table .sticky-col-1,
    .sa-time-matrix-table .sticky-col-2 { position: sticky; z-index: 7; background: #fff; background-clip: padding-box; border-right: 1px solid rgba(148, 163, 184, 0.18); }
    .sa-time-matrix-table .sticky-col-1 { left: 0; width: 72px; min-width: 72px; max-width: 72px; box-shadow: none; }
    .sa-time-matrix-table .sticky-col-2 { left: 72px; width: 280px; min-width: 280px; max-width: 280px; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; box-shadow: none; }
    .sa-time-matrix-table tbody tr:nth-child(even) td { background: #fcfdfc; }
    .sa-time-matrix-table tbody tr:hover td { background: #f4fbf8; }
    .sa-time-matrix-table td.text-end { text-align: center; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .sa-time-matrix-table td.sticky-col-1,
    .sa-time-matrix-table td.sticky-col-2 { z-index: 6; }
    .sa-time-matrix-total-row td,
    .sa-time-matrix-total-row .sticky-col-1,
    .sa-time-matrix-total-row .sticky-col-2 { background: #effaf5; font-weight: 800; }
    .sa-time-matrix-table tbody tr td.sticky-col-1,
    .sa-time-matrix-table tbody tr td.sticky-col-2 { box-shadow: none; }
    .sa-time-matrix-branch-name { color: var(--sa-ink); font-size: 0.84rem; font-weight: 850; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center; }
    .sa-time-matrix-branch-code { margin-top: 0.1rem; color: var(--sa-muted); font-size: 0.68rem; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center; }
    .sa-time-matrix-sticky-stack { min-width: 0; overflow: hidden; }
    .sa-time-matrix-period-cell { min-width: 78px; }
    .sa-time-matrix-period-revenue,
    .sa-time-matrix-period-single { border-left: 1px solid rgba(148, 163, 184, 0.08); }
    .sa-time-matrix-period-revenue { color: var(--sa-green-dark); font-weight: 850; background: #fcfffd; }
    .sa-time-matrix-period-orders { color: #475569; font-weight: 800; background: #ffffff; }
    .sa-time-matrix-summary-col,
    .sa-time-matrix-summary-cell,
    .sa-time-matrix-change-cell { background: #fbfcfd; }
    .sa-time-matrix-summary-cell { color: var(--sa-ink); font-weight: 850; }
    .sa-time-matrix-change-cell { min-width: 124px; }
    .sa-time-matrix-change { color: var(--sa-green-dark); font-weight: 800; }
    .sa-time-matrix-empty,
    .sa-time-matrix-error { min-height: 164px; padding: 1.1rem; border: 1px dashed var(--sa-border); border-radius: 14px; display: grid; place-items: center; text-align: center; color: var(--sa-muted); background: #fff; }
    .sa-time-matrix-empty i,
    .sa-time-matrix-error i { margin-bottom: 0.35rem; color: var(--sa-green); font-size: 1.4rem; }
    .sa-time-matrix-empty strong,
    .sa-time-matrix-error strong { color: var(--sa-ink); font-size: 0.84rem; }
    .sa-time-matrix-pagination { min-height: 52px; padding-top: 0.15rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; color: var(--sa-muted); font-size: 0.74rem; }

    .sa-filter-form { padding: 1rem 1.1rem; border-bottom: 1px solid var(--sa-border); display: grid; grid-template-columns: minmax(220px, 1.45fr) repeat(3, minmax(140px, 0.68fr)) auto; gap: 0.85rem; }
    .sa-control { min-width: 0; height: var(--sa-control-height); border: 1px solid var(--sa-border); border-radius: 12px; padding: 0 0.9rem; background: #fff; color: var(--sa-ink); font-size: 0.9rem; outline: 0; }
    .sa-control:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,0.1); }
    .sa-filter-actions { display: flex; gap: 0.7rem; }
    .sa-branch-compare-header { min-height: 0; padding: 0.72rem 0.85rem; align-items: center; gap: 0.7rem; border-bottom: 1px solid var(--sa-border); }
    .sa-branch-compare-header-copy { min-width: 150px; display: grid; gap: 0.14rem; }
    .sa-branch-compare-title { margin: 0; color: var(--sa-ink); font-size: 0.94rem; font-weight: 900; line-height: 1.2; }
    .sa-branch-compare-subtitle { color: var(--sa-muted); font-size: 0.7rem; line-height: 1.2; }
    .sa-branch-compare-tools { min-width: 0; display: flex; align-items: center; justify-content: flex-end; gap: 0.55rem; margin-left: auto; }
    .sa-branch-period-group { min-width: 0; display: inline-flex; align-items: center; gap: 0.42rem; }
    .sa-branch-period-label { color: var(--sa-muted); font-size: 0.7rem; font-weight: 800; white-space: nowrap; }
    .sa-branch-period-switcher { display: inline-flex; align-items: center; gap: 0.12rem; padding: 0.16rem; border: 1px solid var(--sa-border); border-radius: 10px; background: #f8faf9; white-space: nowrap; }
    .sa-branch-period-link { min-height: 32px; padding: 0.32rem 0.64rem; border-radius: 8px; font-size: 0.74rem; line-height: 1; box-shadow: none; }
    .sa-branch-compare-form { padding: 0.62rem 0.85rem; gap: 0.55rem; align-items: end; background: #fbfcfb; }
    .sa-branch-compare-form-compact { grid-template-columns: minmax(280px, 1.6fr) minmax(180px, 0.58fr) auto; }
    .sa-branch-compare-form-range { grid-template-columns: minmax(138px, 0.46fr) minmax(138px, 0.46fr) minmax(230px, 1.2fr) minmax(170px, 0.58fr) auto; }
    .sa-branch-filter-field label { display: block; margin: 0 0 0.24rem; color: var(--sa-muted); font-size: 0.68rem; font-weight: 800; line-height: 1; }
    .sa-branch-filter-field .sa-control { height: 38px; border-radius: 9px; font-size: 0.82rem; }
    .sa-branch-compare-actions { justify-content: flex-end; align-self: end; gap: 0.42rem; white-space: nowrap; }
    .sa-branch-compare-actions .sa-btn { min-height: 38px; padding: 0.38rem 0.72rem; border-radius: 9px; font-size: 0.78rem; }
    .sa-branch-add-btn { min-height: 34px; padding: 0.34rem 0.72rem; border-radius: 9px; white-space: nowrap; font-size: 0.78rem; }
    .sa-branch-compare-summary { padding: 0.5rem 0.85rem 0.2rem; display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; flex-wrap: nowrap; }
    .sa-branch-compare-summary-text { color: var(--sa-muted); font-size: 0.72rem; white-space: nowrap; }
    .sa-branch-compare-summary-meta { display: inline-flex; align-items: center; gap: 0.28rem; flex-wrap: nowrap; white-space: nowrap; }
    .sa-branch-compare-summary-meta .sa-state { padding: 0.25rem 0.48rem; font-size: 0.68rem; line-height: 1.1; }
    .sa-branch-ranking-table th { padding: 0.62rem 0.72rem; background: #f8faf9; color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; text-align: left; text-transform: uppercase; letter-spacing: 0.02em; line-height: 1.15; vertical-align: middle; }
    .sa-branch-ranking-table td { padding: 0.68rem 0.72rem; border-top: 1px solid #edf1ef; color: #374151; font-size: 0.84rem; vertical-align: middle; }

    .sa-table-wrap { overflow-x: auto; }
    .sa-table { width: 100%; min-width: 970px; border-collapse: collapse; }
    .sa-table th { padding: 0.95rem 1rem; background: #f8faf9; color: var(--sa-muted); font-size: 0.84rem; font-weight: 800; text-align: left; text-transform: uppercase; letter-spacing: 0.02em; }
    .sa-table td { padding: 0.82rem 1rem; border-top: 1px solid #edf1ef; color: #374151; font-size: 0.92rem; vertical-align: middle; }
    .sa-admin-cell { gap: 0.85rem; }
    .sa-avatar { width: 44px; height: 44px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; overflow: hidden; background: var(--sa-green-soft); color: var(--sa-green-dark); font-size: 0.82rem; font-weight: 800; }
    .sa-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sa-admin-name { color: var(--sa-ink); font-weight: 800; }
    .sa-admin-email { margin-top: 0.12rem; color: var(--sa-muted); font-size: 0.72rem; }
    .sa-role, .sa-state, .sa-presence { border-radius: 999px; padding: 0.34rem 0.62rem; display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 800; white-space: nowrap; }
    .sa-role-super { background: #fff3cd; color: #8a4b08; }
    .sa-role-admin { background: #e0f2fe; color: #075985; }
    .sa-state-active { background: #dcfce7; color: #166534; }
    .sa-state-locked { background: #fee2e2; color: #991b1b; }
    .sa-presence::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .sa-presence-online { color: #15803d; background: #f0fdf4; }
    .sa-presence-away { color: #a16207; background: #fefce8; }
    .sa-presence-offline { color: #6b7280; background: #f3f4f6; }
    .sa-actions { gap: 0.35rem; }
    .sa-action-btn { width: 34px; height: 34px; border: 1px solid var(--sa-border); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #fff; color: var(--sa-muted); text-decoration: none; }
    .sa-action-btn:hover { border-color: var(--sa-green); color: var(--sa-green); }
    .sa-action-btn.danger:hover { border-color: #dc2626; color: #dc2626; }
    .sa-action-btn:disabled,
    .sa-action-btn[aria-disabled="true"],
    .sa-action-btn.sa-action-btn-disabled {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }

    .sa-pagination { min-height: 58px; padding: 0.8rem 1rem; border-top: 1px solid var(--sa-border); justify-content: space-between; gap: 1rem; color: var(--sa-muted); font-size: 0.74rem; }
    .sa-page-links { display: flex; gap: 0.3rem; }
    .sa-page-link { min-width: 34px; height: 34px; border: 1px solid var(--sa-border); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #fff; color: var(--sa-muted); text-decoration: none; font-size: 0.74rem; }
    .sa-page-link.active { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .sa-page-link.disabled { pointer-events: none; opacity: 0.45; }

    .sa-activity-list { padding: 0.25rem 0.9rem; }
    .sa-activity { position: relative; padding: 0.85rem 0 0.85rem 2rem; border-bottom: 1px solid #edf1ef; }
    .sa-activity:last-child { border-bottom: 0; }
    .sa-activity-icon { position: absolute; left: 0; top: 0.88rem; width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green); font-size: 0.82rem; }
    .sa-activity p { margin: 0; color: #374151; font-size: 0.84rem; line-height: 1.5; }
    .sa-activity strong { color: var(--sa-ink); }
    .sa-activity time { display: block; margin-top: 0.15rem; color: #9ca3af; font-size: 0.78rem; }
    .sa-empty { min-height: 190px; padding: 1.4rem; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--sa-muted); text-align: center; }
    .sa-empty i { margin-bottom: 0.55rem; color: var(--sa-green); font-size: 1.6rem; }
    .sa-empty strong { color: var(--sa-ink); font-size: 0.82rem; }

    .sa-security-grid { padding: 1rem; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
    .sa-security-item { min-height: 90px; padding: 0.95rem; border: 1px solid var(--sa-border); border-radius: 12px; gap: 0.7rem; }
    .sa-security-item i { color: var(--sa-green); font-size: 1.1rem; }
    .sa-security-value { color: var(--sa-ink); font-size: 1.15rem; font-weight: 800; }
    .sa-security-label { color: var(--sa-muted); font-size: 0.72rem; }

    .sa-health-list { padding: 0.4rem 1rem; }
    .sa-health-row { min-height: 58px; border-bottom: 1px solid #edf1ef; justify-content: space-between; gap: 1rem; }
    .sa-health-row:last-child { border-bottom: 0; }
    .sa-health-name { display: flex; align-items: center; gap: 0.55rem; color: var(--sa-ink); font-size: 0.8rem; font-weight: 750; }
    .sa-health-name i { color: var(--sa-green); }
    .sa-health-value { color: var(--sa-muted); font-size: 0.74rem; font-weight: 650; text-align: right; }
    .sa-health-value.success { color: #15803d; }
    .sa-health-value.danger { color: #b91c1c; }

    .sa-permissions { padding: 1rem; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.65rem; }
    .sa-permission { min-height: 64px; padding: 0.85rem 0.95rem; border: 1px solid var(--sa-border); border-radius: 12px; display: flex; align-items: center; gap: 0.7rem; color: #374151; font-size: 0.84rem; font-weight: 700; }
    .sa-permission i { color: var(--sa-green); }

    /* Keep the existing overview as the source of truth; the analytics layout is retained for later incremental updates. */
    .analytics-dashboard { display: none; }
    .analytics-dashboard .sa-title { font-size: clamp(1.5rem, 2vw, 2rem); letter-spacing: -0.04em; }
    .analytics-dashboard .sa-subtitle { font-size: 0.82rem; }
    .analytics-periods { display: flex; flex-wrap: wrap; gap: 0.2rem; padding: 0.25rem; border: 1px solid var(--sa-border); border-radius: 10px; background: #fff; }
    .analytics-period { border: 0; border-radius: 10px; padding: 0.72rem 1rem; background: transparent; color: var(--sa-muted); font-size: 0.84rem; font-weight: 750; text-decoration: none; cursor: pointer; }
    .analytics-period.active { background: var(--sa-green); color: #fff; box-shadow: 0 3px 8px rgba(13,147,115,.2); }
    .analytics-filters { display: grid; grid-template-columns: repeat(5, minmax(140px, 1fr)) auto; gap: 0.85rem; align-items: end; padding: 1.1rem; border: 2px solid var(--sa-green); border-radius: 16px; background: linear-gradient(180deg, #fbfffd, #fff); }
    .analytics-filter-label { display: block; margin: 0 0 0.48rem; color: var(--sa-muted); font-size: 0.78rem; font-weight: 800; }
    .analytics-filter-label.strong { color: var(--sa-ink); }
    .analytics-filter-intro { display: none; }
    .analytics-control { width: 100%; height: 44px; border: 1px solid var(--sa-border); border-radius: 10px; padding: 0 0.85rem; background: #fff; color: var(--sa-ink); font-size: 0.8rem; outline: 0; }
    .analytics-control:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,.1); }
    .analytics-filter-actions { display: flex; gap: 0.45rem; justify-content: flex-end; }
    .analytics-filter-actions .sa-btn { white-space: nowrap; }
    .analytics-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.85rem; }
    .analytics-kpi { min-width: 0; padding: 1rem; border: 1px solid var(--sa-border); border-radius: 12px; background: #fff; box-shadow: 0 4px 14px rgba(17,24,39,.03); }
    .analytics-kpi-head { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; }
    .analytics-kpi-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; background: #e8f8f3; color: var(--sa-green); }
    .analytics-kpi:nth-child(2) .analytics-kpi-icon { background: #fff0f0; color: #ef6a6a; }
    .analytics-kpi:nth-child(3) .analytics-kpi-icon { background: #e8f7fb; color: #1da5bf; }
    .analytics-kpi:nth-child(4) .analytics-kpi-icon { background: #eaf3ff; color: #3484dc; }
    .analytics-kpi:nth-child(5) .analytics-kpi-icon { background: #f0eaff; color: #8261d7; }
    .analytics-kpi-label { color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; }
    .analytics-kpi-value { margin-top: 0.8rem; color: var(--sa-ink); font-size: clamp(1.32rem, 2vw, 1.85rem); font-weight: 850; white-space: nowrap; letter-spacing: -0.02em; }
    .analytics-kpi-growth { margin-top: 0.34rem; color: var(--sa-green); font-size: 0.8rem; font-weight: 750; }
    .analytics-spark { height: 30px; margin-top: 0.65rem; display: flex; align-items: end; gap: 3px; }
    .analytics-spark i { flex: 1; min-height: 3px; border-radius: 2px 2px 0 0; background: #2bbd96; opacity: .9; }
    .analytics-grid { display: grid; grid-template-columns: minmax(230px, .95fr) minmax(300px, 1fr) minmax(300px, 1fr); gap: 0.95rem; }
    .analytics-card { min-width: 0; border: 1px solid var(--sa-border); border-radius: 12px; background: #fff; overflow: hidden; }
    .analytics-card-header { display: flex; justify-content: space-between; align-items: center; gap: .7rem; padding: 1rem 1rem .7rem; }
    .analytics-card-title { margin: 0; color: var(--sa-ink); font-size: .98rem; font-weight: 850; text-transform: uppercase; }
    .analytics-card-link { color: var(--sa-green); font-size: .75rem; font-weight: 750; text-decoration: none; }
    .analytics-card-body { padding: .4rem 1rem 1rem; }
    .top-product { display: grid; grid-template-columns: 24px 32px minmax(0,1fr) auto; align-items: center; gap: .65rem; min-height: 48px; border-bottom: 1px solid #edf1ef; }
    .top-product:last-child { border-bottom: 0; }
    .top-product-rank { color: var(--sa-ink); font-size: .88rem; font-weight: 850; }
    .top-product-thumb { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: linear-gradient(145deg, #e5fff5, #b8e9d6); color: var(--sa-green-dark); font-size: .75rem; font-weight: 850; }
    .top-product-name { min-width: 0; color: #374151; font-size: .76rem; font-weight: 750; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .top-product-bar { height: 4px; margin-top: 4px; border-radius: 99px; background: #edf0ef; overflow: hidden; }
    .top-product-bar span { display: block; height: 100%; border-radius: inherit; background: var(--sa-green); }
    .top-product-units { color: var(--sa-muted); font-size: .72rem; white-space: nowrap; }
    .analytics-chart { height: 260px; padding: .55rem .95rem .95rem; }
    .analytics-bars { height: 190px; display: flex; align-items: end; gap: .45rem; padding: 0 0 .2rem; border-bottom: 1px solid var(--sa-border); }
    .analytics-bar-column { flex: 1; min-width: 0; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: end; gap: .3rem; }
    .analytics-bar-column span { width: min(38px, 80%); min-height: 3px; border-radius: 4px 4px 0 0; background: linear-gradient(180deg, #20ae88, #078567); }
    .analytics-bar-label { color: var(--sa-muted); font-size: .64rem; white-space: nowrap; }
    .analytics-line-chart { width: 100%; height: 190px; overflow: visible; }
    .analytics-line-chart .grid-line { stroke: #e7eeeb; stroke-dasharray: 3 4; stroke-width: 1; }
    .analytics-line-chart .trend-line { fill: none; stroke: var(--sa-green); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .analytics-line-chart .comparison-line { fill: none; stroke: #4f9ee7; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .analytics-legend { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: .45rem; color: var(--sa-muted); font-size: .7rem; }
    .analytics-legend span::before { content: ''; width: 15px; height: 2px; display: inline-block; margin: 0 .25rem .15rem 0; background: var(--sa-green); }
    .analytics-legend span:last-child::before { background: #4f9ee7; }
    .analytics-insights { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.95rem; }
    .analytics-insight { padding: 1rem; border: 1px solid var(--sa-border); border-radius: 12px; background: linear-gradient(180deg, #ffffff, #fbfffd); }
    .analytics-insight-kicker { color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .analytics-insight-value { margin-top: 0.55rem; color: var(--sa-ink); font-size: 1.22rem; font-weight: 850; line-height: 1.35; }
    .analytics-insight-meta { margin-top: 0.28rem; color: var(--sa-green); font-size: 0.72rem; font-weight: 750; }
    .analytics-insight-note { margin-top: 0.28rem; color: var(--sa-muted); font-size: 0.72rem; line-height: 1.5; }
    .analytics-branch-section { border: 1px solid var(--sa-border); border-radius: 14px; background: #fff; overflow: hidden; }
    .analytics-branch-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; padding: 0 1rem 1rem; }
    .analytics-branch-card { padding: 1rem; border: 1px solid #e8efec; border-radius: 14px; background: linear-gradient(180deg, #ffffff, #f9fdfb); box-shadow: 0 10px 24px rgba(17, 24, 39, 0.03); }
    .analytics-branch-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
    .analytics-branch-rank { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green); font-size: 0.8rem; font-weight: 900; }
    .analytics-branch-name { color: var(--sa-ink); font-size: 0.86rem; font-weight: 850; line-height: 1.35; }
    .analytics-branch-code { margin-top: 0.15rem; color: var(--sa-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .analytics-branch-share { color: var(--sa-green); font-size: 0.8rem; font-weight: 850; white-space: nowrap; }
    .analytics-branch-revenue { margin-top: 0.8rem; color: var(--sa-ink); font-size: 1.3rem; font-weight: 900; }
    .analytics-branch-growth { margin-top: 0.2rem; color: var(--sa-green); font-size: 0.72rem; font-weight: 800; }
    .analytics-branch-growth.down { color: #dc2626; }
    .analytics-branch-progress { height: 7px; margin-top: 0.85rem; border-radius: 999px; background: #edf2ef; overflow: hidden; }
    .analytics-branch-progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #12b98a, #0d9373); }
    .analytics-branch-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; margin-top: 1rem; }
    .analytics-branch-stat { min-width: 0; }
    .analytics-branch-stat-label { color: var(--sa-muted); font-size: 0.66rem; font-weight: 800; text-transform: uppercase; }
    .analytics-branch-stat-value { margin-top: 0.18rem; color: var(--sa-ink); font-size: 0.9rem; font-weight: 850; white-space: nowrap; }
    .analytics-table-card { display: none; }
    .analytics-table { min-width: 760px; }
    .analytics-table th { background: #fbfcfc; text-transform: none; font-size: .62rem; }
    .analytics-table td { font-size: .66rem; }
    .branch-product-layout { display: grid; grid-template-columns: minmax(0, 0.36fr) minmax(0, 0.64fr); gap: 0.85rem; padding: 0.9rem; }
    .branch-product-panel { min-width: 0; border: 1px solid var(--sa-border); border-radius: 14px; background: #fff; overflow: hidden; }
    .branch-product-panel-header { padding: 0.84rem 0.9rem 0.7rem; border-bottom: 1px solid var(--sa-border); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
    .branch-product-panel-title { margin: 0; color: var(--sa-ink); font-size: 0.84rem; font-weight: 900; line-height: 1.2; }
    .branch-product-panel-note { margin: 0.12rem 0 0; color: var(--sa-muted); font-size: 0.68rem; line-height: 1.4; }
    .branch-product-controls { border: 1px solid var(--sa-border); border-radius: 18px; background: linear-gradient(180deg, #fbfffe 0%, #ffffff 100%); box-shadow: 0 12px 28px rgba(17, 24, 39, 0.035); overflow: hidden; }
    .branch-product-toolbar { padding: 0.88rem 0.95rem 0.72rem; display: flex; align-items: center; justify-content: space-between; gap: 0.9rem; border-bottom: 1px solid #eef3f1; }
    .branch-product-toolbar-copy { min-width: 0; display: grid; gap: 0.14rem; }
    .branch-product-toolbar-title { margin: 0; color: var(--sa-ink); font-size: 0.92rem; font-weight: 900; line-height: 1.2; letter-spacing: -0.01em; }
    .branch-product-toolbar-note { margin: 0; color: var(--sa-muted); font-size: 0.72rem; line-height: 1.4; }
    .branch-product-toolbar-meta { display: flex; flex-wrap: wrap; gap: 0.28rem; margin-top: 0.28rem; }
    .branch-product-toolbar-meta .sa-state { padding: 0.23rem 0.5rem; font-size: 0.64rem; line-height: 1.15; }
    .branch-product-toolbar-actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.48rem; flex-wrap: wrap; margin-left: auto; }
    .branch-product-period-switcher { display: inline-flex; align-items: center; gap: 0.16rem; padding: 0.18rem; border: 1px solid var(--sa-border); border-radius: 999px; background: #fff; white-space: nowrap; }
    .branch-product-period-link { min-height: 32px; padding: 0.3rem 0.66rem; border-radius: 999px; font-size: 0.74rem; line-height: 1; }
    .branch-product-add-btn { min-height: 32px; padding: 0.3rem 0.72rem; border-radius: 999px; white-space: nowrap; font-size: 0.76rem; }
    .branch-product-filterbar { padding: 0.76rem 0.95rem 0.88rem; display: grid; grid-template-columns: minmax(240px, 1.45fr) repeat(3, minmax(136px, 0.82fr)) minmax(150px, auto); gap: 0.62rem 0.7rem; align-items: end; background: linear-gradient(180deg, #fff 0%, #fcfefe 100%); }
    .branch-product-filter-field { min-width: 0; }
    .branch-product-filter-label { display: block; margin: 0 0 0.3rem; color: #475569; font-size: 0.7rem; font-weight: 800; line-height: 1.2; }
    .branch-product-filter-actions { display: flex; gap: 0.45rem; justify-content: flex-end; align-items: end; white-space: nowrap; }
    .branch-product-filterbar .sa-control { height: 38px; border-radius: 10px; font-size: 0.78rem; }
    .branch-product-list-wrap { max-height: 560px; overflow-y: auto; overflow-x: hidden; }
    .branch-product-list-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .branch-product-list-table th { padding: 0.58rem 0.62rem; background: #f8faf9; color: var(--sa-muted); font-size: 0.6rem; font-weight: 850; text-align: left; text-transform: none; letter-spacing: 0; line-height: 1.15; }
    .branch-product-list-table td { padding: 0.62rem 0.62rem; border-top: 1px solid #edf1ef; font-size: 0.7rem; vertical-align: top; min-width: 0; }
    .branch-product-list-table th:first-child,
    .branch-product-list-table td:first-child { width: 38px; }
    .branch-product-list-table th:nth-child(2),
    .branch-product-list-table td:nth-child(2) { width: 43%; }
    .branch-product-list-table th:nth-child(3),
    .branch-product-list-table td:nth-child(3) { width: 31%; }
    .branch-product-list-table th:nth-child(4),
    .branch-product-list-table td:nth-child(4) { width: 84px; }
    .branch-product-select { display: block; width: 100%; border: 0; background: transparent; padding: 0; text-align: left; color: inherit; cursor: pointer; text-decoration: none; min-width: 0; }
    .branch-product-select:hover .branch-product-name { color: var(--sa-green-dark); }
    .branch-product-select.active { background: #f0fdf4; }
    .branch-product-name { margin: 0; color: var(--sa-ink); font-size: 0.73rem; font-weight: 850; line-height: 1.25; overflow-wrap: anywhere; }
    .branch-product-code { margin-top: 0.06rem; color: var(--sa-muted); font-size: 0.62rem; font-weight: 700; overflow-wrap: anywhere; }
    .branch-product-subtext { margin-top: 0.1rem; color: var(--sa-muted); font-size: 0.66rem; line-height: 1.3; overflow-wrap: anywhere; }
    .branch-product-badge { border-radius: 999px; padding: 0.24rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.66rem; font-weight: 800; white-space: nowrap; }
    .branch-product-badge.up { background: #ecfdf5; color: #15803d; }
    .branch-product-badge.down { background: #fef2f2; color: #b91c1c; }
    .branch-product-badge.flat { background: #f8fafc; color: #475569; }
    .branch-product-detail { min-width: 0; display: grid; gap: 0.85rem; padding: 1rem; }
    .branch-product-detail-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; }
    .branch-product-detail-title { margin: 0; color: var(--sa-ink); font-size: 1.15rem; font-weight: 900; line-height: 1.3; }
    .branch-product-detail-meta { margin-top: 0.22rem; color: var(--sa-muted); font-size: 0.76rem; line-height: 1.45; }
    .branch-product-detail-chiprow { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .branch-product-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.85rem; }
    .branch-product-summary-card { min-width: 0; padding: 0.9rem; border: 1px solid #e9eeec; border-radius: 12px; background: linear-gradient(180deg, #fff, #fbfffd); }
    .branch-product-summary-label { color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; line-height: 1.2; }
    .branch-product-summary-value { margin-top: 0.34rem; color: var(--sa-ink); font-size: 1.15rem; font-weight: 900; white-space: nowrap; }
    .branch-product-summary-note { margin-top: 0.2rem; color: var(--sa-green); font-size: 0.72rem; font-weight: 750; }
    .branch-product-mini-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
    .branch-product-mini { padding: 0.75rem 0.8rem; border: 1px solid #edf1ef; border-radius: 12px; background: #f8fcfb; }
    .branch-product-mini-label { color: var(--sa-muted); font-size: 0.68rem; font-weight: 800; line-height: 1.2; }
    .branch-product-mini-value { margin-top: 0.2rem; color: var(--sa-ink); font-size: 0.92rem; font-weight: 850; white-space: nowrap; }
    .branch-product-toplist { display: grid; gap: 0.65rem; }
    .branch-product-toprow { padding: 0.9rem 0.95rem; border: 1px solid #edf1ef; border-radius: 12px; display: grid; grid-template-columns: 30px 52px minmax(0, 1fr) 96px 96px 100px; gap: 0.85rem; align-items: center; background: #fff; }
    .branch-product-toprank { width: 30px; height: 30px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green-dark); font-size: 0.8rem; font-weight: 900; }
    .branch-product-thumb { width: 48px; height: 48px; border-radius: 12px; background: #f3f4f6; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.8rem; font-weight: 800; }
    .branch-product-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .branch-product-topname { min-width: 0; color: var(--sa-ink); font-size: 0.8rem; font-weight: 850; line-height: 1.35; }
    .branch-product-topmeta { margin-top: 0.16rem; color: var(--sa-muted); font-size: 0.78rem; line-height: 1.35; }
    .branch-product-topmetric { color: var(--sa-ink); font-size: 0.84rem; font-weight: 800; white-space: nowrap; text-align: right; }
    .branch-product-topmetric strong { display: block; color: var(--sa-green-dark); font-size: 0.9rem; font-weight: 900; }
    .branch-product-empty-detail { min-height: 320px; border: 1px dashed #d8e3df; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--sa-muted); text-align: center; padding: 1.1rem; }
    .branch-product-loading { opacity: 0.72; pointer-events: none; }
    .focus-product-layout { display: grid; grid-template-columns: minmax(0, 0.38fr) minmax(0, 0.62fr); gap: 0.9rem; padding: 0.85rem; }
    .focus-product-panel { min-width: 0; border: 1px solid var(--sa-border); border-radius: 12px; background: #fff; overflow: hidden; }
    .focus-product-panel-header { padding: 1rem 1rem 0.8rem; border-bottom: 1px solid var(--sa-border); display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
    .focus-product-panel-title { margin: 0; color: var(--sa-ink); font-size: 0.95rem; font-weight: 900; }
    .focus-product-panel-note { margin: 0.12rem 0 0; color: var(--sa-muted); font-size: 0.76rem; line-height: 1.45; }
    .focus-product-panel-body { display: grid; gap: 0.75rem; }
    .focus-product-form { padding: 0.85rem 0.9rem 0; display: grid; gap: 0.55rem; }
    .focus-product-form-row { display: flex; align-items: flex-end; gap: 0.75rem; }
    .focus-product-search { flex: 1 1 auto; min-width: 0; }
    .focus-product-search .sa-control { height: 40px; }
    .focus-product-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; justify-content: flex-end; }
    .focus-product-candidate-list { padding: 0 0.9rem 0.9rem; display: grid; gap: 0.45rem; }
    .focus-product-candidate { display: grid; grid-template-columns: 44px minmax(0, 1fr) auto; gap: 0.75rem; align-items: center; padding: 0.82rem 0.9rem; border: 1px solid #edf1ef; border-radius: 12px; text-decoration: none; color: inherit; background: linear-gradient(180deg, #fff, #fbfffd); }
    .focus-product-candidate:hover { border-color: var(--sa-green); color: inherit; }
    .focus-product-candidate.active { border-color: var(--sa-green); background: #f0fdf4; }
    .focus-product-candidate-thumb { width: 44px; height: 44px; border-radius: 10px; overflow: hidden; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.78rem; font-weight: 800; }
    .focus-product-candidate-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .focus-product-candidate-name { margin: 0; color: var(--sa-ink); font-size: 0.71rem; font-weight: 850; line-height: 1.35; }
    .focus-product-candidate-meta { margin-top: 0.12rem; color: var(--sa-muted); font-size: 0.72rem; line-height: 1.35; display: flex; flex-wrap: wrap; gap: 0.35rem 0.65rem; }
    .focus-product-candidate-meta span { white-space: nowrap; }
    .focus-product-candidate-status { border-radius: 999px; padding: 0.25rem 0.55rem; background: #f8fafc; color: #475569; font-size: 0.7rem; font-weight: 800; white-space: nowrap; }
    .focus-product-selected { margin: 0 1rem; padding: 0.9rem; border: 1px solid #e8efec; border-radius: 12px; background: linear-gradient(180deg, #fbfffd, #fff); }
    .focus-product-selected-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
    .focus-product-selected-title { margin: 0; color: var(--sa-ink); font-size: 0.9rem; font-weight: 900; line-height: 1.35; }
    .focus-product-selected-subtitle { margin: 0.12rem 0 0; color: var(--sa-muted); font-size: 0.62rem; line-height: 1.4; }
    .focus-product-selected-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.55rem; margin-top: 0.55rem; }
    .focus-product-selected-stat { min-width: 0; padding: 0.58rem 0.7rem; border: 1px solid #edf1ef; border-radius: 10px; background: #f8fcfb; }
    .focus-product-selected-stat-label { color: var(--sa-muted); font-size: 0.66rem; font-weight: 800; line-height: 1.2; text-transform: uppercase; }
    .focus-product-selected-stat-value { margin-top: 0.12rem; color: var(--sa-ink); font-size: 0.92rem; font-weight: 900; line-height: 1.2; white-space: nowrap; }
    .focus-product-selected-chiprow { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.55rem; }
    .focus-product-summary-grid { padding: 0 0.9rem 0.1rem; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.85rem; }
    .focus-product-summary-card { min-width: 0; padding: 0.82rem; border: 1px solid #e9eeec; border-radius: 12px; background: linear-gradient(180deg, #fff, #fbfffd); }
    .focus-product-summary-label { color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; }
    .focus-product-summary-value { margin-top: 0.24rem; color: var(--sa-ink); font-size: 0.98rem; font-weight: 900; white-space: nowrap; }
    .focus-product-summary-note { margin-top: 0.16rem; color: var(--sa-green); font-size: 0.72rem; font-weight: 750; }
    .focus-product-controls { padding: 0.35rem 1rem 0; display: flex; align-items: center; justify-content: space-between; gap: 0.9rem; flex-wrap: wrap; }
    .focus-product-sort { display: flex; flex-wrap: wrap; gap: 0.35rem; padding: 0.2rem; border: 1px solid var(--sa-border); border-radius: 999px; background: #fff; }
    .focus-product-sort-link { min-height: 40px; padding: 0.42rem 0.8rem; border: 1px solid transparent; border-radius: 999px; background: #fff; color: var(--sa-muted); font-size: 0.82rem; font-weight: 800; text-decoration: none; }
    .focus-product-sort-link.active { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .focus-product-searchbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 0.4rem; align-items: end; padding: 0.15rem 0.9rem 0.35rem; }
    .focus-product-searchbar .sa-control { height: 44px; }
    .focus-product-branch-list { padding: 0.35rem 0.9rem 0.9rem; display: grid; gap: 0.5rem; }
    .focus-product-branch-card { display: grid; grid-template-columns: 34px minmax(0, 1.2fr) minmax(0, 0.95fr) auto; gap: 0.85rem; align-items: center; padding: 0.9rem; border: 1px solid #edf1ef; border-radius: 12px; background: linear-gradient(180deg, #fff, #fbfffd); }
    .focus-product-branch-card.top { border-color: rgba(13,147,115,.22); background: linear-gradient(180deg, #f5fffb, #fff); }
    .focus-product-branch-rank { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green-dark); font-size: 0.76rem; font-weight: 900; }
    .focus-product-branch-name { margin: 0; color: var(--sa-ink); font-size: 0.74rem; font-weight: 900; line-height: 1.35; }
    .focus-product-branch-meta { margin-top: 0.12rem; color: var(--sa-muted); font-size: 0.72rem; line-height: 1.35; display: flex; flex-wrap: wrap; gap: 0.35rem 0.65rem; }
    .focus-product-branch-meta span { white-space: nowrap; }
    .focus-product-branch-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.55rem; }
    .focus-product-branch-stat { min-width: 0; }
    .focus-product-branch-stat-label { color: var(--sa-muted); font-size: 0.55rem; font-weight: 800; text-transform: uppercase; }
    .focus-product-branch-stat-value { margin-top: 0.16rem; color: var(--sa-ink); font-size: 0.72rem; font-weight: 850; white-space: nowrap; }
    .focus-product-branch-badges { display: flex; flex-direction: column; align-items: flex-end; gap: 0.3rem; }
    .focus-product-branch-badge { border-radius: 999px; padding: 0.28rem 0.6rem; background: #f8fafc; color: #475569; font-size: 0.7rem; font-weight: 800; white-space: nowrap; }
    .focus-product-branch-badge.up { background: #ecfdf5; color: #15803d; }
    .focus-product-branch-badge.down { background: #fef2f2; color: #b91c1c; }
    .focus-product-branch-badge.flat { background: #f8fafc; color: #475569; }
    .focus-product-branch-badge.muted { background: #f8fafc; color: #64748b; }
    .focus-product-pagination { min-height: 58px; padding: 0.8rem 1rem 1rem; border-top: 1px solid var(--sa-border); display: flex; align-items: center; justify-content: space-between; gap: 1rem; color: var(--sa-muted); font-size: 0.76rem; }
    .focus-product-empty { padding: 1.55rem 1.1rem; color: var(--sa-muted); text-align: center; font-size: 0.8rem; }
    @media (max-width: 991px) {
        .branch-product-layout { grid-template-columns: 1fr; }
        .branch-product-summary-grid,
        .branch-product-mini-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .branch-product-toprow { grid-template-columns: 24px 44px minmax(0, 1fr); }
        .branch-product-topmetric { text-align: left; }
        .focus-product-layout { grid-template-columns: 1fr; }
        .focus-product-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .focus-product-branch-card { grid-template-columns: 30px minmax(0, 1fr); }
        .focus-product-branch-stats { grid-column: 1 / -1; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .focus-product-branch-badges { grid-column: 1 / -1; align-items: flex-start; flex-direction: row; flex-wrap: wrap; }
    }
    @media (max-width: 640px) {
        .branch-product-summary-grid,
        .branch-product-mini-grid { grid-template-columns: 1fr; }
        .branch-product-toprow { grid-template-columns: 24px minmax(0, 1fr); }
        .focus-product-summary-grid { grid-template-columns: 1fr; }
        .focus-product-searchbar { grid-template-columns: 1fr; }
        .focus-product-pagination { align-items: flex-start; flex-direction: column; }
    }
    .analytics-table-total td { background: #f5fbf8; color: var(--sa-ink); font-weight: 850; }
    .analytics-positive { color: var(--sa-green); font-weight: 800; }
    .analytics-empty { padding: 2rem 1rem; color: var(--sa-muted); text-align: center; font-size: .7rem; }
    .legacy-analytics-bar { margin-bottom: 0; padding: 1rem 1.1rem 1.05rem; border: 1px solid var(--sa-border); border-radius: 16px; background: linear-gradient(180deg, #fbfffd, #fff); box-shadow: 0 8px 24px rgba(17, 24, 39, 0.03); }
    .legacy-analytics-head { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 0.85rem; align-items: start; margin-bottom: 0.85rem; }
    .legacy-analytics-head-copy { min-width: 0; }
    .legacy-analytics-head-copy .sa-panel-title { margin: 0; }
    .legacy-analytics-head-copy .sa-panel-note { margin: 0.25rem 0 0; }
    .legacy-analytics-presets { display: inline-flex; flex-wrap: nowrap; gap: 0.2rem; padding: 0.22rem; border: 1px solid var(--sa-border); border-radius: 16px; background: #fff; max-width: 100%; overflow-x: auto; overflow-y: hidden; scrollbar-width: none; }
    .legacy-analytics-presets::-webkit-scrollbar { display: none; }
    .legacy-analytics-period-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0.58rem 0.9rem;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--sa-muted);
        font-size: 0.82rem;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }
    .legacy-analytics-period-btn.active { background: var(--sa-green); color: #fff; box-shadow: 0 10px 20px rgba(13,147,115,.18); }
    .legacy-analytics-form { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 0.7rem 0.75rem; align-items: end; grid-auto-flow: row dense; }
    .legacy-analytics-field { min-width: 0; }
    .legacy-analytics-field--branch { min-width: 0; }
    .legacy-analytics-field--compare { min-width: 0; }
    .legacy-analytics-label { display: block; margin: 0 0 0.26rem; color: var(--sa-muted); font-size: 0.7rem; font-weight: 800; line-height: 1.2; }
    .legacy-analytics-control { width: 100%; min-width: 0; height: 42px; border: 1px solid var(--sa-border); border-radius: 10px; padding: 0 0.8rem; background: #fff; color: var(--sa-ink); font-size: 0.82rem; outline: 0; }
    .legacy-analytics-control:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,0.1); }
    .legacy-analytics-range { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; grid-column: 1 / -1; }
    .legacy-analytics-actions { display: inline-flex; gap: 0.5rem; align-items: center; justify-content: flex-end; white-space: nowrap; }
    .legacy-analytics-actions .sa-btn { min-width: 0; }
    .legacy-analytics-period-row { display: grid; gap: 0.55rem; }
    .legacy-analytics-summary { margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px solid #ebf1ef; color: var(--sa-muted); font-size: 0.8rem; font-weight: 750; line-height: 1.5; }
    .legacy-analytics-summary-line { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .legacy-analytics-bar--compact { padding: 0.62rem 0.78rem; border-radius: 14px; }
    .legacy-analytics-form--compact { display: block; }
    .legacy-analytics-toolbar { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }
    .legacy-analytics-toolbar-title { min-width: 185px; display: inline-flex; align-items: center; gap: 0.42rem; color: var(--sa-ink); font-size: 0.82rem; font-weight: 900; white-space: nowrap; }
    .legacy-analytics-toolbar-title i { color: var(--sa-green); font-size: 0.9rem; }
    .legacy-analytics-bar--compact .legacy-analytics-presets { margin-left: auto; padding: 0.14rem; border-radius: 12px; }
    .legacy-analytics-bar--compact .legacy-analytics-period-btn { min-height: 31px; padding: 0.34rem 0.62rem; border-radius: 9px; font-size: 0.72rem; }
    .legacy-analytics-actions--compact { width: auto !important; margin-left: 0.1rem; gap: 0.35rem; flex: 0 0 auto; }
    .legacy-analytics-actions--compact .sa-btn { min-height: 33px; padding: 0.34rem 0.62rem; border-radius: 9px; font-size: 0.72rem; }
    .legacy-analytics-filter-plain { order: 1; width: 100%; min-width: 0; margin: 0; padding: 0; border: 0; background: transparent; box-shadow: none; }
    .legacy-analytics-filter-plain .legacy-analytics-toolbar { justify-content: flex-end; padding: 0; }
    .legacy-analytics-filter-plain .legacy-analytics-presets { margin-left: 0; }
    .legacy-analytics-filter-plain .legacy-analytics-range-row { margin-top: 0.45rem; padding: 0.45rem 0 0; }
    .legacy-overview[data-business-overview-region].is-refreshing,
    .sa-business-section[data-business-analysis-region].is-refreshing,
    .legacy-top-products[data-top-products-region].is-refreshing { pointer-events: none; cursor: progress; }
    .legacy-analytics-range-row { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #edf2f0; }
    .legacy-analytics-range-row .legacy-analytics-range { max-width: 420px; margin-left: auto; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .legacy-analytics-range-row .legacy-analytics-control { height: 36px; border-radius: 9px; font-size: 0.76rem; }
    .legacy-analytics-range-row .legacy-analytics-label { margin-bottom: 0.2rem; font-size: 0.66rem; }
    @media (min-width: 1400px) {
        .legacy-analytics-form > .legacy-analytics-actions { grid-column: 10 / -1; grid-row: 1; width: auto; align-self: end; justify-self: end; }
        .legacy-analytics-form > .legacy-analytics-period-row { grid-column: 1 / span 8; grid-row: 1; }
    }
    .sa-branch-scope { position: relative; min-width: 0; }
    .sa-branch-scope-trigger { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.65rem; padding: 0 0.85rem; text-align: left; }
    .sa-branch-scope-trigger-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 700; }
    .sa-branch-scope-trigger-count { flex: 0 0 auto; min-width: 54px; height: 22px; padding: 0 0.5rem; border-radius: 999px; background: #eefbf7; color: var(--sa-green-dark); display: inline-flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 900; white-space: nowrap; }
    .sa-branch-scope-panel { position: absolute; z-index: 40; top: calc(100% + 0.5rem); left: 0; width: min(100%, 420px); min-width: 320px; max-height: 420px; padding: 0.85rem; border: 1px solid var(--sa-border); border-radius: 14px; background: #fff; box-shadow: 0 20px 42px rgba(17,24,39,.16); overflow: hidden; }
    .sa-branch-scope-panel[hidden] { display: none !important; }
    .sa-branch-scope-panel.align-right { left: auto; right: 0; }
    .sa-branch-scope-panel-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
    .sa-branch-scope-panel-title { color: var(--sa-ink); font-size: 0.92rem; font-weight: 900; }
    .sa-branch-scope-panel-note { margin-top: 0.2rem; color: var(--sa-muted); font-size: 0.72rem; line-height: 1.4; }
    .sa-branch-scope-close { width: 34px; height: 34px; border: 0; border-radius: 10px; background: #f6f9f8; color: var(--sa-muted); display: inline-flex; align-items: center; justify-content: center; }
    .sa-branch-scope-searchbar { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem; padding: 0 0.8rem; border: 1px solid var(--sa-border); border-radius: 12px; background: #fff; }
    .sa-branch-scope-searchbar i { color: var(--sa-muted); font-size: 0.9rem; }
    .sa-branch-scope-search { width: 100%; height: 42px; border: 0; outline: 0; font-size: 0.82rem; background: transparent; }
    .sa-branch-scope-actions { display: flex; gap: 0.45rem; margin-top: 0.75rem; }
    .sa-btn-soft { min-height: 36px; padding: 0 0.8rem; background: #f7fbfa; color: var(--sa-ink); border-color: #dce9e4; font-size: 0.76rem; font-weight: 800; }
    .sa-branch-scope-list { max-height: 230px; overflow: auto; margin-top: 0.75rem; padding-right: 0.1rem; overscroll-behavior: contain; }
    .sa-branch-scope-option { display: flex; align-items: flex-start; gap: 0.7rem; padding: 0.7rem 0.75rem; border: 1px solid #edf2ef; border-radius: 12px; cursor: pointer; }
    .sa-branch-scope-option + .sa-branch-scope-option { margin-top: 0.45rem; }
    .sa-branch-scope-option:hover { border-color: #c8e7de; background: #f7fbfa; }
    .sa-branch-scope-option input { margin-top: 0.1rem; }
    .sa-branch-scope-option-body { display: flex; flex-direction: column; gap: 0.12rem; min-width: 0; }
    .sa-branch-scope-option-title { color: var(--sa-ink); font-size: 0.82rem; font-weight: 850; line-height: 1.35; }
    .sa-branch-scope-option-meta { color: var(--sa-muted); font-size: 0.72rem; line-height: 1.35; }
    .sa-branch-scope-empty { padding: 1rem 0.75rem; color: var(--sa-muted); font-size: 0.8rem; text-align: center; }
    .sa-branch-scope-footer { display: flex; justify-content: flex-end; margin-top: 0.75rem; }
    .sa-branch-scope-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.45rem; }
    .sa-branch-scope-chip { padding: 0.32rem 0.65rem; border-radius: 999px; background: #eefbf7; color: var(--sa-green-dark); font-size: 0.72rem; font-weight: 800; }
    .sa-branch-scope-chip.muted { background: #f3f4f6; color: var(--sa-muted); }
    .legacy-analytics-hidden { display: none !important; }
    .legacy-top-products { margin-bottom: 1rem; overflow: hidden; border: 1px solid var(--sa-border); border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(17, 24, 39, 0.03); display: flex; flex-direction: column; }
    .legacy-top-products-header { padding: 0.8rem 0.9rem 0.65rem; display: flex; align-items: center; justify-content: space-between; gap: 0.65rem; border-bottom: 1px solid var(--sa-border); }
    .legacy-top-products-title { margin: 0; color: var(--sa-ink); font-size: 0.84rem; font-weight: 850; }
    .legacy-top-products-actions { display: inline-flex; flex-wrap: wrap; gap: 0.35rem; justify-content: flex-end; }
    .legacy-top-products-branch-filter { position: relative; display: inline-flex; align-items: center; min-width: 158px; }
    .legacy-top-products-branch-filter i { position: absolute; left: 0.7rem; color: var(--sa-green); font-size: 0.78rem; pointer-events: none; z-index: 1; }
    .legacy-top-products-branch-select { width: 100%; height: 34px; padding: 0 2rem 0 1.9rem; border: 1px solid var(--sa-border); border-radius: 10px; background: #fff; color: var(--sa-ink); font-size: 0.72rem; font-weight: 800; outline: 0; cursor: pointer; appearance: auto; }
    .legacy-top-products-branch-select:hover { border-color: rgba(13,147,115,.38); background: #fbfffd; }
    .legacy-top-products-branch-select:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,.09); }
    .legacy-top-products-action { min-height: 36px; padding: 0.34rem 0.75rem; border: 1px solid var(--sa-border); border-radius: 999px; background: #fff; color: var(--sa-muted); font-size: 0.72rem; font-weight: 800; text-decoration: none; }
    .legacy-top-products-action.active { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .legacy-top-products-list { padding: 0.65rem 0.85rem 0.85rem; display: grid; gap: 0.4rem; }
    .legacy-top-products-row { display: grid; grid-template-columns: 28px 40px minmax(0, 1fr) minmax(150px, 0.9fr); gap: 0.65rem; align-items: center; padding: 0.6rem 0.7rem; border: 1px solid #edf1ef; border-radius: 11px; background: linear-gradient(180deg, #ffffff, #fbfffd); }
    .legacy-top-products-rank { width: 24px; height: 24px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green-dark); font-size: 0.68rem; font-weight: 900; }
    .legacy-top-products-thumb { width: 38px; height: 38px; border-radius: 9px; overflow: hidden; background: #f3f4f6; flex: 0 0 auto; }
    .legacy-top-products-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .legacy-top-products-name { margin: 0; color: var(--sa-ink); font-size: 0.78rem; font-weight: 850; line-height: 1.3; }
    .legacy-top-products-meta { margin-top: 0.1rem; display: flex; flex-wrap: wrap; gap: 0.32rem 0.55rem; color: var(--sa-muted); font-size: 0.68rem; font-weight: 650; }
    .legacy-top-products-meta span { white-space: nowrap; }
    .legacy-top-products-branch { min-width: 0; text-align: right; }
    .legacy-top-products-branch strong { display: block; color: var(--sa-ink); font-size: 0.72rem; font-weight: 850; line-height: 1.3; }
    .legacy-top-products-branch span { display: block; margin-top: 0.08rem; color: var(--sa-muted); font-size: 0.7rem; line-height: 1.3; }
    .legacy-top-products-empty { padding: 1rem 0.95rem 0.95rem; color: var(--sa-muted); text-align: center; font-size: 0.76rem; }
    .legacy-overview { display: grid; gap: 0.75rem; width: 100%; min-width: 0; padding: 0.75rem; border: 1px solid var(--sa-border); border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #f8fffd 100%); box-shadow: 0 20px 52px rgba(17, 24, 39, 0.05); overflow: hidden; }
    .legacy-page-header { display: flex; }
    .legacy-page-header { align-items: flex-start; }
    .legacy-overview > * { min-width: 0; }
    .sa-section,
    .sa-stats,
    .sa-overview-grid,
    .sa-supplemental-grid,
    .sa-charts-grid,
    .sa-health-grid,
    .sa-ops-grid,
    .sa-analytics-shell,
    .legacy-analytics-bar,
    .legacy-top-products { min-width: 0; width: 100%; }
    .sa-header-actions {
        flex: 0 1 auto;
        min-width: 0;
        max-width: min(100%, 560px);
        display: grid;
        justify-items: end;
        gap: 0.6rem;
        align-content: start;
    }
    .sa-header-action-row {
        display: inline-flex;
        flex-wrap: nowrap;
        justify-content: flex-end;
        gap: 0.6rem;
        width: auto;
        max-width: 100%;
    }
    .sa-header-action-row .sa-btn { min-width: 0; }
    .sa-period-shortcuts {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 0.2rem;
        padding: 0.22rem;
        border: 1px solid var(--sa-border);
        border-radius: 14px;
        background: #fff;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
    }
    .sa-period-shortcuts::-webkit-scrollbar { display: none; }
    .sa-period-shortcut {
        flex: 0 0 auto;
        min-height: 40px;
        padding: 0.58rem 0.9rem;
        border-radius: 10px;
        color: var(--sa-muted);
        font-size: 0.82rem;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        line-height: 1;
    }
    .sa-period-shortcut.active { background: var(--sa-green); color: #fff; box-shadow: 0 10px 20px rgba(13,147,115,.18); }
    .legacy-analytics-bar { order: 1; padding: 1rem 1.15rem 1.05rem; border-radius: 18px; background: linear-gradient(180deg, #fcfffe, #ffffff); }
    .sa-kpi-grid { order: 2; margin-bottom: 0 !important; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .sa-quick-section { order: 3; }
    .sa-business-section { order: 4; }
    .sa-supplemental-section { order: 5; }
    .sa-ops-grid { order: 90; }
    .sa-health-section { order: 100; }
    .legacy-analytics-head { gap: 1rem; margin-bottom: 0.75rem; align-items: center; flex-wrap: nowrap; }
    .legacy-analytics-presets { gap: 0.16rem; padding: 0.2rem; }
    .legacy-analytics-period-btn { padding: 0.56rem 0.74rem; font-size: 0.78rem; }
    .legacy-analytics-form { gap: 0.6rem 0.7rem; }
    .legacy-analytics-label { margin: 0 0 0.3rem; font-size: 0.72rem; }
    .legacy-analytics-control { height: 42px; border-radius: 10px; font-size: 0.82rem; }
    .legacy-analytics-range { gap: 0.45rem; }
    .legacy-analytics-actions { gap: 0.6rem; }
    .legacy-analytics-form,
    .legacy-analytics-range,
    .legacy-analytics-period-row,
    .legacy-analytics-compare-grid,
    .legacy-analytics-actions { min-width: 0; width: 100%; }
    .legacy-analytics-form > .legacy-analytics-field--compare { grid-column: 1 / span 4; grid-row: 1; }
    .legacy-analytics-form > .legacy-analytics-field--branch { grid-column: 5 / span 5; grid-row: 1; }
    .legacy-analytics-form > .legacy-analytics-actions { grid-column: 10 / -1; grid-row: 1; width: auto; align-self: end; justify-self: end; }
    .legacy-analytics-form > .legacy-analytics-period-row { grid-column: 1 / -1; grid-row: 2; }
    .legacy-analytics-form > .legacy-analytics-compare-grid { grid-column: 1 / -1; grid-row: 3; }
    .legacy-analytics-compare-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.85rem; grid-column: 1 / -1; }
    .legacy-analytics-compare-grid .legacy-analytics-field[data-analytics-compare-group="range"],
    .legacy-analytics-compare-grid .legacy-analytics-field[data-analytics-compare-group="week"] { grid-column: 1 / -1; }
    .legacy-analytics-period-row .legacy-analytics-range { gap: 0.4rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .legacy-analytics-period-row .legacy-analytics-control { height: 40px; border-radius: 9px; font-size: 0.78rem; }
    .sa-section { display: grid; gap: 0.8rem; }
    .sa-section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 0.85rem; }
    .sa-section-kicker { margin: 0 0 0.2rem; color: var(--sa-green); font-size: 0.74rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.04em; }
    .sa-section-title { margin: 0; color: var(--sa-ink); font-size: clamp(1.06rem, 1.25vw, 1.25rem); font-weight: 900; letter-spacing: -0.02em; }
    .sa-section-copy { max-width: 840px; margin: 0.2rem 0 0; color: var(--sa-muted); font-size: 0.84rem; }
    .sa-overview-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.8rem; align-items: stretch; }
    .sa-overview-grid > .sa-panel,
    .sa-overview-grid > .legacy-top-products { height: 100%; }
    .sa-overview-card-header { min-height: 0; padding: 0 0 0.6rem; margin-bottom: 0.08rem; border-bottom: 1px solid var(--sa-border); align-items: center; }
    .sa-quick-branches { display: flex; flex-direction: column; padding: 0.8rem; }
    .sa-quick-branch-list { display: grid; gap: 0.42rem; }
    .sa-quick-branch-row { padding: 0.62rem 0.72rem; border: 1px solid #edf2ef; border-radius: 12px; display: grid; grid-template-columns: 30px minmax(0, 1fr) auto; gap: 0.6rem; align-items: center; background: linear-gradient(180deg, #fff, #fbfffd); }
    .sa-quick-branch-rank { width: 30px; height: 30px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green-dark); font-size: 0.72rem; font-weight: 900; }
    .sa-quick-branch-name { margin: 0; color: var(--sa-ink); font-size: 0.82rem; font-weight: 850; }
    .sa-quick-branch-meta { margin-top: 0.1rem; color: var(--sa-muted); font-size: 0.7rem; line-height: 1.32; }
    .sa-quick-branch-metric { text-align: right; }
    .sa-quick-branch-metric strong { display: block; color: var(--sa-green); font-size: 0.88rem; font-weight: 900; }
    .sa-quick-branch-metric span { display: block; margin-top: 0.08rem; color: var(--sa-muted); font-size: 0.7rem; }

    /* Tổng quan nhanh: biểu đồ cột doanh thu theo chi nhánh/thời gian, độc lập với bộ lọc khác. */
    .sa-revenue-trend-card { position: relative; display: flex; flex-direction: column; min-width: 0; overflow: hidden; }
    .sa-revenue-trend-card.is-refreshing { cursor: progress; }
    .sa-revenue-trend-card.is-refreshing::after { content: ''; position: absolute; z-index: 45; left: 0; top: 0; height: 2px; width: 34%; border-radius: 999px; background: var(--sa-green); animation: saQuickTrendLoading .72s ease-in-out infinite alternate; pointer-events: none; }
    @keyframes saQuickTrendLoading { from { transform: translateX(-25%); opacity: .4; } to { transform: translateX(220%); opacity: 1; } }
    .sa-revenue-trend-head { position: relative; display: grid; gap: 0.48rem; padding: 0.72rem 0.78rem 0.62rem; border-bottom: 1px solid var(--sa-border); }
    .sa-revenue-trend-toolbar { display: flex; align-items: center; gap: 0.5rem; min-width: 0; flex-wrap: nowrap; }
    .sa-revenue-trend-title-wrap { min-width: max-content; margin-right: auto; }
    .sa-revenue-trend-title { margin: 0; color: var(--sa-ink); font-size: 0.84rem; font-weight: 900; white-space: nowrap; }
    .sa-revenue-trend-branch { position: relative; flex: 0 0 154px; min-width: 0; }
    .sa-revenue-trend-branch i { position: absolute; left: 0.62rem; top: 50%; transform: translateY(-50%); color: var(--sa-green); font-size: 0.7rem; pointer-events: none; }
    .sa-revenue-trend-select { width: 100%; height: 31px; padding: 0 1.65rem 0 1.62rem; border: 1px solid var(--sa-border); border-radius: 9px; background: #fff; color: var(--sa-ink); font-size: 0.67rem; font-weight: 800; outline: 0; }
    .sa-revenue-trend-select:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,.08); }
    .sa-revenue-trend-periods { display: flex; align-items: center; gap: 0.08rem; padding: 0.12rem; border: 1px solid #e5ebe8; border-radius: 9px; background: #f7faf9; flex: 0 0 auto; max-width: 100%; overflow-x: auto; scrollbar-width: none; }
    .sa-revenue-trend-periods::-webkit-scrollbar { display: none; }
    .sa-revenue-trend-period { flex: 0 0 auto; min-height: 27px; padding: 0.28rem 0.46rem; border: 0; border-radius: 7px; background: transparent; color: #4b5563; font-size: 0.64rem; font-weight: 850; cursor: pointer; white-space: nowrap; }
    .sa-revenue-trend-period:hover { color: var(--sa-green-dark); background: #eef8f4; }
    .sa-revenue-trend-period.active { background: var(--sa-green); color: #fff; box-shadow: none; }
    .sa-revenue-trend-range { position: absolute; z-index: 35; top: calc(100% - 0.18rem); right: 0.78rem; width: min(360px, calc(100% - 1.56rem)); padding: 0.72rem; border: 1px solid #dce8e4; border-radius: 12px; background: #fff; box-shadow: 0 16px 36px rgba(15, 23, 42, .14); }
    .sa-revenue-trend-range::before { content: ''; position: absolute; top: -6px; right: 18px; width: 11px; height: 11px; border-left: 1px solid #dce8e4; border-top: 1px solid #dce8e4; background: #fff; transform: rotate(45deg); }
    .sa-revenue-trend-range[hidden] { display: none !important; }
    .sa-revenue-trend-range-head { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.58rem; }
    .sa-revenue-trend-range-title { margin: 0; color: var(--sa-ink); font-size: 0.72rem; font-weight: 900; }
    .sa-revenue-trend-range-close { width: 27px; height: 27px; border: 0; border-radius: 8px; background: #f4f7f6; color: #66736d; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .sa-revenue-trend-range-close:hover { background: #eaf5f1; color: var(--sa-green-dark); }
    .sa-revenue-trend-range-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.48rem; }
    .sa-revenue-trend-range-field label { display: block; margin-bottom: 0.2rem; color: var(--sa-muted); font-size: 0.62rem; font-weight: 800; }
    .sa-revenue-trend-range-field input { width: 100%; height: 34px; border: 1px solid var(--sa-border); border-radius: 9px; padding: 0 0.55rem; color: var(--sa-ink); background: #fff; font-size: 0.67rem; outline: 0; }
    .sa-revenue-trend-range-field input:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,.07); }
    .sa-revenue-trend-range-actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.38rem; margin-top: 0.62rem; padding-top: 0.55rem; border-top: 1px solid #edf2f0; }
    .sa-revenue-trend-range-actions .sa-btn { min-height: 31px; padding: 0.3rem 0.62rem; border-radius: 8px; font-size: 0.67rem; }
    .sa-revenue-trend-body { padding: 0.72rem 0.78rem 0.78rem; flex: 1; min-height: 0; display: flex; }
    .sa-revenue-trend-chart { position: relative; width: 100%; height: auto; min-height: 205px; flex: 1 1 auto; padding: 1.15rem 0.7rem 1.9rem; border: 1px dashed #dce6e2; border-radius: 12px; background: linear-gradient(180deg, #f3fbf8 0%, rgba(247,250,249,.38) 100%); overflow: hidden; }
    .sa-revenue-trend-bars { height: 100%; display: flex; align-items: end; justify-content: space-between; gap: 0.42rem; }
    .sa-revenue-trend-bars.is-sparse { justify-content: center; gap: clamp(1rem, 4vw, 2.5rem); }
    .sa-revenue-trend-bars.is-sparse .sa-revenue-trend-col { flex: 0 1 118px; max-width: 118px; }
    .sa-revenue-trend-bars.is-dense { min-width: max(100%, 760px); }
    .sa-revenue-trend-col { flex: 1 1 0; min-width: 0; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; position: relative; }
    .sa-revenue-trend-bar-wrap { width: min(52px, 72%); height: 100%; display: flex; align-items: end; }
    .sa-revenue-trend-bar { width: 100%; min-height: 3px; border-radius: 7px 7px 0 0; background: linear-gradient(180deg, #9fdecf 0%, #16a37f 100%); opacity: .88; transition: height .2s ease, transform .16s ease, opacity .16s ease; cursor: pointer; }
    .sa-revenue-trend-col:hover .sa-revenue-trend-bar { opacity: 1; transform: translateY(-2px); }
    .sa-revenue-trend-bar.is-zero { height: 3px !important; min-height: 3px; background: #dce8e4; opacity: 1; }
    .sa-revenue-trend-label { position: absolute; top: calc(100% + 0.48rem); left: 50%; transform: translateX(-50%); max-width: 76px; color: #596660; font-size: 0.59rem; font-weight: 750; line-height: 1.15; white-space: nowrap; }
    .sa-revenue-trend-empty { width: 100%; min-height: 205px; flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.35rem; border: 1px dashed #dce6e2; border-radius: 12px; background: #f8fbfa; color: var(--sa-muted); font-size: 0.72rem; text-align: center; }
    .sa-revenue-trend-empty i { color: #9bb8ae; font-size: 1.2rem; }
    .sa-analytics-shell { padding: 1rem; background: linear-gradient(180deg, #fbfffd 0%, #ffffff 100%); }
    .sa-analytics-tabs { display: flex; flex-wrap: wrap; gap: 0.4rem; padding-bottom: 0.15rem; border-bottom: 1px solid #ebf1ef; }
    .sa-analytics-tab { min-height: 42px; padding: 0.6rem 0.88rem; border: 1px solid var(--sa-border); border-radius: 12px; background: #f7faf9; color: var(--sa-muted); font-size: 0.8rem; font-weight: 800; cursor: pointer; transition: all 0.18s ease; }
    .sa-analytics-tab.active { border-color: rgba(13,147,115,.25); background: var(--sa-green-soft); color: var(--sa-green-dark); }
    .sa-analytics-panels { margin-top: 0.8rem; }
    .sa-analytics-panel[hidden] { display: none !important; }
    .sa-analytics-panel > .sa-panel { box-shadow: none; }
    .sa-analytics-panel > .sa-panel,
    .sa-analytics-panel > [data-branch-product-detail-region],
    .sa-analytics-panel > [data-product-branch-performance-region] { margin-top: 0 !important; }
    .sa-supplemental-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .sa-supplemental-grid .sa-panel { overflow: visible; }
    .sa-charts-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .sa-ops-grid { display: grid; gap: 1rem; }
    .sa-health-grid { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.7fr); gap: 1rem; }

    /* Tổng quan: bộ lọc kinh doanh gọn trên một hàng. */
    .legacy-analytics-bar.legacy-analytics-bar--compact { padding: 0.58rem 0.72rem; border-radius: 14px; }
    .legacy-analytics-form.legacy-analytics-form--compact { display: block; width: 100%; }
    .legacy-analytics-toolbar { display: flex; align-items: center; gap: 0.48rem; width: 100%; min-width: 0; }
    .legacy-analytics-toolbar-title { min-width: 178px; display: inline-flex; align-items: center; gap: 0.38rem; color: var(--sa-ink); font-size: 0.79rem; font-weight: 900; white-space: nowrap; }
    .legacy-analytics-bar--compact .legacy-analytics-presets { margin-left: auto; width: auto; flex: 0 1 auto; padding: 0.12rem; border-radius: 11px; }
    .legacy-analytics-bar--compact .legacy-analytics-period-btn { min-height: 30px; padding: 0.31rem 0.57rem; border-radius: 8px; font-size: 0.69rem; }
    .legacy-analytics-actions.legacy-analytics-actions--compact { width: auto; flex: 0 0 auto; gap: 0.32rem; margin-left: 0.08rem; }
    .legacy-analytics-actions--compact .sa-btn { min-height: 32px; padding: 0.31rem 0.56rem; border-radius: 8px; font-size: 0.7rem; }
    .legacy-analytics-range-row { margin-top: 0.45rem; padding-top: 0.45rem; border-top: 1px solid #edf2f0; }
    .legacy-analytics-range-row .legacy-analytics-range { width: min(100%, 400px); margin-left: auto; grid-template-columns: repeat(2, minmax(0, 1fr)); }

    /* Bộ chọn chi nhánh riêng cho card bán chạy; chỉ lọc card này. */
    .legacy-top-products-header { min-height: 48px; padding: 0.62rem 0.72rem; }
    .legacy-top-products-actions { flex: 0 0 auto; }
    .legacy-top-products-branch-filter { min-width: 152px; max-width: 190px; }
    .legacy-top-products-branch-select { height: 32px; border-radius: 9px; font-size: 0.69rem; }

    @media (max-width: 1519.98px) {
        .sa-branch-compare-header { align-items: flex-start; flex-wrap: wrap; }
        .sa-branch-compare-tools { margin-left: 0; width: 100%; justify-content: space-between; }
        .sa-branch-compare-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sa-branch-compare-form-compact { grid-template-columns: minmax(220px, 1.35fr) minmax(160px, 0.72fr) auto; }
        .sa-branch-compare-form-range { grid-template-columns: repeat(2, minmax(150px, 0.75fr)) minmax(220px, 1.25fr); }
        .sa-branch-compare-form-range .sa-branch-compare-actions { grid-column: 1 / -1; }
        .sa-branch-compare-actions { justify-content: flex-end; }
        .sa-branch-compare-summary { flex-wrap: wrap; }
    }
    @media (max-width: 1399.98px) {
        .sa-header { gap: 0.9rem 1rem; }
        .sa-header-copy { max-width: 620px; }
        .sa-header-actions { max-width: 500px; gap: 0.55rem; }
        .sa-header-action-row { gap: 0.5rem; }
        .sa-period-shortcuts { gap: 0.16rem; padding: 0.2rem; }
        .sa-period-shortcut { padding: 0.56rem 0.74rem; font-size: 0.78rem; }
        .sa-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .analytics-filters { grid-template-columns: repeat(3, minmax(160px, 1fr)); }
        .analytics-filter-actions { grid-column: 1 / -1; justify-content: flex-start; }
        .analytics-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .analytics-grid .analytics-card:first-child { grid-row: span 2; }
        .analytics-insights { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .analytics-branch-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-grid-main { grid-template-columns: 1fr; }
        .sa-filter-form { grid-template-columns: minmax(200px, 1fr) repeat(3, minmax(120px, 0.7fr)); }
        .sa-filter-actions { grid-column: 1 / -1; }
        .sa-branch-compare-form,
        .sa-branch-compare-form-compact,
        .sa-branch-compare-form-range { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-branch-compare-actions { grid-column: 1 / -1; justify-content: flex-start; }
        .legacy-top-products-row { grid-template-columns: 28px 46px minmax(0, 1fr); }
        .legacy-top-products-branch { grid-column: 1 / -1; text-align: left; }
        .sa-overview-grid, .sa-health-grid, .sa-charts-grid { grid-template-columns: 1fr; }
        .sa-supplemental-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .legacy-analytics-field--branch { grid-column: auto; }
        .sa-time-matrix-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-time-matrix-export { grid-column: 1 / -1; }
    }
    @media (max-width: 1199.98px) {
        .branch-product-toolbar { flex-direction: column; align-items: stretch; }
        .branch-product-toolbar-actions { width: 100%; justify-content: flex-start; }
        .branch-product-filterbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .branch-product-filter-actions { grid-column: 1 / -1; justify-content: flex-end; }
    }
    @media (max-width: 1366.98px) {
        .sa-header {
            flex-direction: column;
            align-items: stretch;
        }
        .sa-header-copy { max-width: none; }
        .sa-header-actions {
            max-width: none;
            width: 100%;
            justify-items: stretch;
        }
        .sa-header-action-row {
            justify-content: flex-start;
            width: 100%;
        }
        .sa-period-shortcuts {
            width: 100%;
        }
    }
    @media (max-width: 1199.98px) {
        .sa-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-security-grid, .sa-permissions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-supplemental-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767.98px) {
        .sa-revenue-trend-toolbar { align-items: stretch; flex-wrap: wrap; }
        .sa-revenue-trend-title-wrap { width: 100%; margin-right: 0; }
        .sa-revenue-trend-branch { flex: 1 1 150px; }
        .sa-revenue-trend-periods { flex: 1 1 auto; }
        .sa-revenue-trend-range { position: fixed; top: auto; right: 0.8rem; bottom: 0.8rem; left: 0.8rem; width: auto; max-width: none; }
        .sa-revenue-trend-range::before { display: none; }
        .sa-revenue-trend-range-fields { grid-template-columns: 1fr; }
        .sa-revenue-trend-chart { overflow-x: auto; }
        .sa-revenue-trend-bars { min-width: 520px; }
        .analytics-filters { grid-template-columns: 1fr 1fr; }
        .analytics-filter-actions { grid-column: span 2; flex-wrap: wrap; }
        .analytics-kpis, .analytics-grid { grid-template-columns: 1fr; }
        .analytics-insights, .analytics-branch-list { grid-template-columns: 1fr; }
        .analytics-grid .analytics-card:first-child { grid-row: auto; }
        .sa-header { align-items: stretch; flex-direction: column; }
        .sa-grid-2 { grid-template-columns: 1fr; }
        .sa-filter-form { grid-template-columns: 1fr; }
        .sa-filter-actions { grid-column: auto; }
        .sa-branch-compare-header { align-items: flex-start; flex-direction: column; }
        .sa-branch-compare-tools { width: 100%; flex-wrap: wrap; justify-content: space-between; margin-left: 0; }
        .sa-branch-period-group { width: 100%; align-items: flex-start; flex-direction: column; gap: 0.28rem; }
        .sa-branch-period-switcher { overflow-x: auto; max-width: 100%; }
        .sa-branch-compare-form { grid-template-columns: 1fr; }
        .sa-branch-compare-actions { justify-content: stretch; }
        .sa-branch-compare-actions .sa-btn { flex: 1 1 0; justify-content: center; }
        .sa-branch-compare-summary { align-items: flex-start; }
        .sa-branch-compare-summary-text,
        .sa-branch-compare-summary-meta { white-space: normal; }
        .legacy-analytics-form { grid-template-columns: 1fr; }
        .legacy-analytics-period-row,
        .legacy-analytics-range { grid-template-columns: 1fr; }
        .legacy-analytics-compare-grid { grid-template-columns: 1fr; }
        .legacy-analytics-actions { justify-content: stretch; }
        .legacy-analytics-form > .legacy-analytics-field--compare,
        .legacy-analytics-form > .legacy-analytics-field--branch,
        .legacy-analytics-form > .legacy-analytics-actions,
        .legacy-analytics-form > .legacy-analytics-period-row,
        .legacy-analytics-form > .legacy-analytics-compare-grid { grid-column: auto; grid-row: auto; justify-self: stretch; width: 100%; }
        .branch-product-toolbar-meta { gap: 0.25rem; }
        .branch-product-toolbar-actions { justify-content: flex-start; }
        .branch-product-period-switcher { width: 100%; overflow-x: auto; }
        .branch-product-filterbar { grid-template-columns: 1fr; }
        .branch-product-filter-actions { justify-content: flex-start; flex-wrap: wrap; }
        .branch-product-filter-actions .sa-btn { flex: 1 1 140px; }
        .legacy-top-products-row { grid-template-columns: 24px 40px minmax(0, 1fr); }
        .sa-pagination { align-items: flex-start; flex-direction: column; }
        .sa-header-actions { justify-items: stretch; }
        .sa-header-action-row { flex-wrap: wrap; }
        .sa-header-action-row .sa-btn { flex: 1 1 calc(50% - 0.3rem); }
        .legacy-analytics-presets { width: 100%; }
        .legacy-analytics-period-btn { text-align: center; }
        .sa-kpi-grid { grid-template-columns: 1fr; }
        .sa-quick-branch-row { grid-template-columns: 42px minmax(0, 1fr); }
        .sa-quick-branch-metric { grid-column: 1 / -1; text-align: left; }
        .sa-section-heading { align-items: flex-start; flex-direction: column; }
        .legacy-analytics-toolbar { flex-wrap: wrap; }
        .legacy-analytics-toolbar-title { width: 100%; min-width: 0; }
        .legacy-analytics-bar--compact .legacy-analytics-presets { margin-left: 0; flex: 1 1 100%; }
        .legacy-analytics-actions.legacy-analytics-actions--compact { margin-left: auto; }
        .legacy-top-products-header { align-items: stretch; flex-direction: column; }
        .legacy-top-products-actions,
        .legacy-top-products-branch-filter { width: 100%; max-width: none; }
    }
    @media (max-width: 575.98px) {
        .sa-stats, .sa-security-grid, .sa-permissions { grid-template-columns: 1fr; }
        .sa-chart-bars { gap: 0.25rem; }
        .sa-chart-value { font-size: 0.65rem; }
    }
</style>

<div class="sa-page">
    @php
        // Trang tổng quan luôn dùng toàn bộ chi nhánh và không dùng kỳ đối chiếu.
        $analyticsSelectedBranchIds = [];
        $analyticsBranchScopeLabel = 'Tất cả chi nhánh';
        $analyticsCompareType = 'none';
        $analyticsPeriodType = (string) $analyticsContext->periodType;
        $analyticsPeriodPresets = [
            'day' => 'Hôm nay',
            'week' => 'Tuần này',
            'month' => 'Tháng này',
            'year' => 'Năm nay',
            'range' => 'Tùy chọn',
        ];
        $analyticsSummaryPeriod = match ($analyticsPeriodType) {
            'day', 'week', 'month', 'year' => $analyticsPeriodPresets[$analyticsPeriodType] ?? ($analyticsContext->displayLabel ?? 'Tất cả thời gian'),
            'range' => $analyticsContext->currentStart && $analyticsContext->currentEnd
                ? ($analyticsContext->currentStart->isSameDay($analyticsContext->currentEnd)
                    ? $analyticsContext->currentStart->format('d/m/Y')
                    : $analyticsContext->currentStart->format('d/m/Y').' – '.$analyticsContext->currentEnd->format('d/m/Y'))
                : ($analyticsPeriodPresets[$analyticsPeriodType] ?? ($analyticsContext->displayLabel ?? 'Tất cả thời gian')),
            default => $analyticsContext->displayLabel ?? 'Tất cả thời gian',
        };
        $analyticsSummaryLine = $analyticsSummaryPeriod.' · Tất cả chi nhánh';
    @endphp

    <header class="sa-header legacy-page-header">
        <div class="sa-header-copy">
            <p class="sa-kicker">Chill Drink / Tổng quan hệ thống</p>
            <h1 class="sa-title">Tổng quan &amp; Phân tích hệ thống</h1>
            <p class="sa-subtitle">Theo dõi hiệu quả kinh doanh, chi nhánh, sản phẩm và tình trạng vận hành.</p>
        </div>
        <div class="sa-header-actions">
            <div class="sa-header-action-row">
                <a href="{{ route('admin.group-orders.index') }}" class="sa-btn">
                    <i class="bi bi-people-fill"></i> Quản lý đơn nhóm
                </a>
                <button type="button" class="sa-btn sa-btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                    <i class="bi bi-person-plus"></i> Thêm Admin
                </button>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success sa-alert"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger sa-alert"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif

    {{-- LEGACY OVERVIEW - CURRENTLY ACTIVE --}}
    <div class="legacy-overview" data-business-overview-region>
        <form class="legacy-analytics-form legacy-analytics-form--compact legacy-analytics-filter-plain" method="GET" action="{{ route('admin.super-admin') }}" aria-label="Bộ lọc thời gian" data-legacy-analytics-form
                data-analytics-default-date="{{ now()->format('Y-m-d') }}"
                data-analytics-default-week="{{ now()->format('o-\WW') }}"
                data-analytics-default-month="{{ now()->format('Y-m') }}"
                data-analytics-default-year="{{ now()->format('Y') }}"
                data-analytics-default-range-start="{{ now()->startOfMonth()->format('Y-m-d') }}"
                data-analytics-default-range-end="{{ now()->format('Y-m-d') }}">
                <input type="hidden" name="q" value="{{ $filters['search'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <input type="hidden" name="role" value="{{ $filters['role'] }}">
                <input type="hidden" name="created" value="{{ $filters['created'] }}">
                <input type="hidden" name="analytics_product_sort" value="quantity">
                <input type="hidden" name="branch_search" value="{{ request('branch_search') }}">
                <input type="hidden" name="branch_sort" value="{{ request('branch_sort', 'revenue') }}">
                <input type="hidden" name="branch_direction" value="{{ request('branch_direction', 'desc') }}">
                <input type="hidden" name="branch_performance" value="{{ request('branch_performance', 'all') }}">
                <input type="hidden" name="branch_per_page" value="{{ request('branch_per_page', 5) }}">
                <input type="hidden" name="branch_page" value="1">
                <input type="hidden" name="quick_trend_period" value="{{ $quickTrendPeriod }}">
                @if($quickTrendBranchId)
                    <input type="hidden" name="quick_trend_branch_id" value="{{ $quickTrendBranchId }}">
                @endif
                @if($quickTrendPeriod === 'range')
                    <input type="hidden" name="quick_trend_start_date" value="{{ $quickTrendStartDate }}">
                    <input type="hidden" name="quick_trend_end_date" value="{{ $quickTrendEndDate }}">
                @endif
                @if($topProductBranchId)
                    <input type="hidden" name="top_product_branch_id" value="{{ $topProductBranchId }}">
                @endif
                <input type="hidden" name="analytics_period_type" value="{{ $analyticsPeriodType }}" data-analytics-period-type-input>
                <input type="hidden" name="analytics_date" value="{{ now()->format('Y-m-d') }}" data-analytics-period-value="day">
                <input type="hidden" name="analytics_week" value="{{ now()->format('o-\WW') }}" data-analytics-period-value="week">
                <input type="hidden" name="analytics_month" value="{{ now()->format('Y-m') }}" data-analytics-period-value="month">
                <input type="hidden" name="analytics_year" value="{{ now()->format('Y') }}" data-analytics-period-value="year">
                <input type="hidden" name="analytics_compare_type" value="none">

                <div class="legacy-analytics-toolbar">
                    <div class="legacy-analytics-presets" role="group" aria-label="Chọn khoảng thời gian">
                        @foreach($analyticsPeriodPresets as $periodValue => $periodLabel)
                            <button
                                type="button"
                                class="legacy-analytics-period-btn {{ $analyticsPeriodType === $periodValue ? 'active' : '' }}"
                                data-analytics-period-preset="{{ $periodValue }}"
                                aria-pressed="{{ $analyticsPeriodType === $periodValue ? 'true' : 'false' }}"
                            >
                                {{ $periodLabel }}
                            </button>
                        @endforeach
                    </div>

                    <div class="legacy-analytics-actions legacy-analytics-actions--compact">
                        <a href="{{ route('admin.super-admin', request()->except(['analytics_period_type', 'analytics_date', 'analytics_week', 'analytics_month', 'analytics_year', 'analytics_start_date', 'analytics_end_date', 'analytics_compare_type', 'analytics_compare_date', 'analytics_compare_month', 'analytics_compare_year', 'analytics_compare_start_date', 'analytics_compare_end_date', 'analytics_branch_id', 'analytics_branch_ids', 'branch_ids', 'analytics_product_sort'])) }}" class="sa-btn legacy-analytics-reset-btn" title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Đặt lại</span>
                        </a>
                        <button type="submit" class="sa-btn sa-btn-primary legacy-analytics-apply-btn">
                            <i class="bi bi-check2"></i>
                            <span>Áp dụng</span>
                        </button>
                    </div>
                </div>

                <div class="legacy-analytics-period-row legacy-analytics-hidden legacy-analytics-range-row" data-analytics-period-group="range">
                    <div class="legacy-analytics-range">
                        <div class="legacy-analytics-field">
                            <label class="legacy-analytics-label" for="analytics-start-date">Từ ngày</label>
                            <input id="analytics-start-date" type="date" name="analytics_start_date" value="{{ $analyticsContext->currentStart?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d') }}" class="legacy-analytics-control" data-analytics-period-range-input>
                        </div>
                        <div class="legacy-analytics-field">
                            <label class="legacy-analytics-label" for="analytics-end-date">Đến ngày</label>
                            <input id="analytics-end-date" type="date" name="analytics_end_date" value="{{ $analyticsContext->currentEnd?->format('Y-m-d') ?? now()->format('Y-m-d') }}" class="legacy-analytics-control" data-analytics-period-range-input>
                        </div>
                    </div>
                </div>
        </form>

    @php
        $businessKpis = [
            ['key' => 'revenue', 'icon' => 'bi-cash-stack', 'label' => 'Tổng doanh thu', 'money' => true],
            ['key' => 'orders', 'icon' => 'bi-bag-check', 'label' => 'Đơn hàng', 'money' => false],
            ['key' => 'customers', 'icon' => 'bi-people', 'label' => 'Khách hàng', 'money' => false, 'note' => 'Không gồm khách đặt nhanh chưa đăng nhập'],
            ['key' => 'items_sold', 'icon' => 'bi-cup-straw', 'label' => 'Sản phẩm bán ra', 'money' => false],
            ['key' => 'average_order_value', 'icon' => 'bi-receipt', 'label' => 'Trung bình mỗi đơn', 'money' => true],
        ];
        // Trang tổng quan Super Admin chỉ xếp hạng sản phẩm theo số lượng.
        $topProductSort = 'quantity';
    @endphp

    <!-- KPI Cards chính - 5 KPI kinh doanh -->
    <section class="sa-stats sa-kpi-grid" aria-label="KPI chính">
        @foreach($businessKpis as $kpi)
            @php
                $metric = $businessSummary[$kpi['key']] ?? [
                    'current_value' => 0,
                    'compare_value' => null,
                    'absolute_change' => null,
                    'percentage_change' => null,
                    'change_state' => 'unavailable',
                    'comparison_label' => 'Không đối chiếu',
                ];
            @endphp
            <article class="sa-stat" data-business-kpi-card="{{ $kpi['key'] }}" style="min-height: 132px; @if($kpi['key'] === 'revenue') background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-color: rgba(13, 147, 115, 0.25); @endif">
                <div class="sa-stat-top">
                    <span class="sa-stat-icon" style="width: 44px; height: 44px; @if($kpi['key'] === 'revenue') background: var(--sa-green); color: #fff; @endif">
                        <i class="bi {{ $kpi['icon'] }}"></i>
                    </span>
                    @if(!empty($kpi['note']))
                        <span class="sa-stat-note">{{ $kpi['note'] }}</span>
                    @endif
                </div>
                <div class="sa-stat-value" style="font-size: 1.58rem; @if($kpi['key'] === 'revenue' || $kpi['key'] === 'average_order_value') color: var(--sa-green); @endif">
                    @if($kpi['money'])
                        {{ number_format((int) $metric['current_value'], 0, ',', '.') }}đ
                    @else
                        {{ number_format((int) $metric['current_value'], 0, ',', '.') }}
                    @endif
                </div>
                <div class="sa-stat-label">{{ $kpi['label'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="sa-section sa-quick-section" aria-labelledby="quick-overview-title">
        <div class="sa-section-heading">
            <div>
                <p class="sa-section-kicker">Tổng quan nhanh</p>
                <h2 class="sa-section-title" id="quick-overview-title">Điểm nóng cần theo dõi ngay</h2>
                <p class="sa-section-copy">Theo dõi xu hướng doanh thu và sản phẩm bán chạy nổi bật.</p>
            </div>
        </div>

        <div class="sa-overview-grid">
            @php
                $quickTrendBuckets = collect($quickBranchTrend['buckets'] ?? []);
                $quickTrendCompactMoney = static function (int $value): string {
                    if ($value >= 1000000000) {
                        return rtrim(rtrim(number_format($value / 1000000000, 1, ',', '.'), '0'), ',').' tỷ';
                    }
                    if ($value >= 1000000) {
                        return rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',').'tr';
                    }
                    if ($value >= 1000) {
                        return number_format((int) round($value / 1000), 0, ',', '.').'k';
                    }
                    return number_format($value, 0, ',', '.').'đ';
                };
            @endphp
            <article class="sa-panel sa-revenue-trend-card" data-quick-branch-trend-region>
                <div class="sa-revenue-trend-head">
                    <div class="sa-revenue-trend-toolbar">
                        <div class="sa-revenue-trend-title-wrap">
                            <h3 class="sa-revenue-trend-title">Xu hướng doanh thu</h3>
                        </div>

                        <div class="sa-revenue-trend-branch">
                            <i class="bi bi-shop"></i>
                            <select class="sa-revenue-trend-select" aria-label="Chọn chi nhánh cho biểu đồ doanh thu" data-quick-trend-branch-select>
                                <option value="">Tất cả chi nhánh</option>
                                @foreach($branches->sortBy('name') as $branchOption)
                                    <option value="{{ $branchOption->id }}" {{ (int) ($quickTrendBranchId ?? 0) === (int) $branchOption->id ? 'selected' : '' }}>{{ $branchOption->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sa-revenue-trend-periods" aria-label="Khoảng thời gian biểu đồ">
                            @foreach(['day' => 'Hôm nay', 'week' => 'Tuần', 'month' => 'Tháng', 'year' => 'Năm', 'range' => 'Tùy chọn'] as $periodKey => $periodLabel)
                                <button type="button" class="sa-revenue-trend-period {{ ($quickTrendPeriod ?? 'week') === $periodKey ? 'active' : '' }}" data-quick-trend-period="{{ $periodKey }}">{{ $periodLabel }}</button>
                            @endforeach
                        </div>
                    </div>

                    <form class="sa-revenue-trend-range" data-quick-trend-range-form hidden>
                        <div class="sa-revenue-trend-range-head">
                            <p class="sa-revenue-trend-range-title">Khoảng thời gian</p>
                            <button type="button" class="sa-revenue-trend-range-close" aria-label="Đóng chọn khoảng thời gian" data-quick-trend-range-close>
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="sa-revenue-trend-range-fields">
                            <div class="sa-revenue-trend-range-field">
                                <label for="quickTrendStartDate">Từ ngày</label>
                                <input id="quickTrendStartDate" type="date" value="{{ $quickTrendStartDate ?: now()->startOfMonth()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" data-quick-trend-start-date>
                            </div>
                            <div class="sa-revenue-trend-range-field">
                                <label for="quickTrendEndDate">Đến ngày</label>
                                <input id="quickTrendEndDate" type="date" value="{{ $quickTrendEndDate ?: now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" data-quick-trend-end-date>
                            </div>
                        </div>
                        <div class="sa-revenue-trend-range-actions">
                            <button type="button" class="sa-btn sa-btn-light" data-quick-trend-range-cancel>Hủy</button>
                            <button type="submit" class="sa-btn sa-btn-primary"><i class="bi bi-check-lg"></i> Xem</button>
                        </div>
                    </form>
                </div>

                <div class="sa-revenue-trend-body">
                    @if($quickTrendBuckets->isNotEmpty())
                        <div class="sa-revenue-trend-chart" aria-label="Biểu đồ cột doanh thu theo thời gian">
                            @php
                                $quickTrendBucketCount = $quickTrendBuckets->count();
                            @endphp
                            <div class="sa-revenue-trend-bars {{ $quickTrendBucketCount <= 4 ? 'is-sparse' : ($quickTrendBucketCount > 14 ? 'is-dense' : '') }}">
                                @foreach($quickTrendBuckets as $bucket)
                                    @php
                                        $bucketRevenue = (int) ($bucket['revenue'] ?? 0);
                                        $barHeight = (int) ($bucket['height'] ?? 0);
                                    @endphp
                                    <div class="sa-revenue-trend-col" title="{{ $bucket['label'] }}: {{ number_format($bucketRevenue, 0, ',', '.') }}đ doanh thu">
                                        <div class="sa-revenue-trend-bar-wrap">
                                            <span class="sa-revenue-trend-bar {{ $bucketRevenue <= 0 ? 'is-zero' : '' }}" style="height: {{ $bucketRevenue > 0 ? $barHeight : 0 }}%;"></span>
                                        </div>
                                        <span class="sa-revenue-trend-label">{{ $bucket['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="sa-revenue-trend-empty">
                            <i class="bi bi-bar-chart"></i>
                            <span>Chưa có dữ liệu cho khoảng đã chọn.</span>
                        </div>
                    @endif
                </div>
            </article>

            @php
                $selectedTopProductBranch = $topProductBranchId
                    ? $branches->firstWhere('id', $topProductBranchId)
                    : null;
            @endphp
            <section class="legacy-top-products" aria-label="Bán chạy toàn hệ thống" data-top-products-region>
                <div class="legacy-top-products-header">
                    <div>
                        <h2 class="legacy-top-products-title">Bán chạy toàn hệ thống</h2>
                    </div>
                    <div class="legacy-top-products-actions">
                        <div class="legacy-top-products-branch-filter">
                            <i class="bi bi-shop"></i>
                            <select
                                class="legacy-top-products-branch-select"
                                aria-label="Lọc sản phẩm bán chạy theo chi nhánh"
                                data-top-products-branch-select
                            >
                                <option value="">Tất cả chi nhánh</option>
                                @foreach($branches->sortBy('name') as $branchOption)
                                    <option value="{{ $branchOption->id }}" {{ (int) $topProductBranchId === (int) $branchOption->id ? 'selected' : '' }}>
                                        {{ $branchOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if($topProducts->isEmpty())
                    <div class="legacy-top-products-empty">
                        {{ $topProductBranchId ? 'Chi nhánh này chưa có sản phẩm được bán trong kỳ đã chọn.' : 'Chưa có sản phẩm được bán trong kỳ đã chọn.' }}
                    </div>
                @else
                    <div class="legacy-top-products-list" data-top-products-list data-current-sort="quantity">
                        @foreach($topProducts as $topProduct)
                            <article
                                class="legacy-top-products-row"
                                data-top-product-row
                                data-top-product-rank="{{ $topProduct['rank'] }}"
                                data-top-product-quantity="{{ (int) ($topProduct['total_quantity'] ?? 0) }}"
                                data-top-product-revenue="{{ (int) ($topProduct['total_revenue'] ?? 0) }}"
                            >
                                <span class="legacy-top-products-rank" data-top-product-rank-label>{{ $topProduct['rank'] }}</span>
                                <div class="legacy-top-products-thumb">
                                    <img src="{{ $topProduct['product_image_url'] }}" alt="{{ $topProduct['product_name'] }}" loading="lazy">
                                </div>
                                <div>
                                    <h3 class="legacy-top-products-name">{{ $topProduct['product_name'] }}</h3>
                                    <div class="legacy-top-products-meta">
                                        <span>{{ number_format($topProduct['total_quantity']) }} sản phẩm</span>
                                        <span>{{ number_format($topProduct['total_revenue'], 0, ',', '.') }}đ</span>
                                    </div>
                                </div>
                                <div class="legacy-top-products-branch">
                                    @if($topProductBranchId)
                                        <strong>{{ $selectedTopProductBranch?->name ?? 'Chi nhánh đã chọn' }}</strong>
                                        <span>{{ number_format($topProduct['total_quantity']) }} sản phẩm · {{ number_format($topProduct['total_revenue'], 0, ',', '.') }}đ</span>
                                    @else
                                        <strong>Mạnh nhất: {{ $topProduct['strongest_branch_name'] }}</strong>
                                        <span>{{ number_format($topProduct['strongest_branch_quantity']) }} sản phẩm · {{ number_format($topProduct['strongest_branch_revenue'], 0, ',', '.') }}đ</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </section>

    <script>
        let superAdminOverviewLoading = false;

        function restoreStableScroll(scrollTop) {
            window.requestAnimationFrame(() => {
                window.scrollTo({ top: scrollTop, left: window.scrollX, behavior: 'auto' });
            });
        }

        async function loadSuperAdminOverviewRegions(url, options = {}) {
            if (superAdminOverviewLoading) {
                return;
            }

            const targetUrl = new URL(url, window.location.origin);
            targetUrl.hash = window.location.hash || '';
            const selectors = options.regionSelectors || [
                '[data-business-overview-region]',
                '[data-business-analysis-region]',
            ];
            const currentScrollTop = window.scrollY;
            const regions = selectors.map((selector) => document.querySelector(selector)).filter(Boolean);
            regions.forEach((region) => region.classList.add('is-refreshing'));
            superAdminOverviewLoading = true;

            try {
                const response = await fetch(targetUrl.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const html = await response.text();
                const nextDoc = new DOMParser().parseFromString(html, 'text/html');
                let replaced = false;

                selectors.forEach((selector) => {
                    const currentRegion = document.querySelector(selector);
                    const nextRegion = nextDoc.querySelector(selector);
                    if (currentRegion && nextRegion) {
                        currentRegion.replaceWith(nextRegion);
                        replaced = true;
                    }
                });

                if (!replaced) {
                    throw new Error('Không tìm thấy vùng Tổng quan để cập nhật.');
                }

                window.history.replaceState({}, '', targetUrl.toString());
                syncLegacyAnalyticsPeriodFields();
                syncLegacyAnalyticsCompareFields();
                if (typeof syncAnalyticsTabFromLocation === 'function') {
                    syncAnalyticsTabFromLocation();
                }
                if (typeof syncLegacyTopProducts === 'function') {
                    syncLegacyTopProducts();
                }
                restoreStableScroll(currentScrollTop);
            } catch (error) {
                console.error('Super Admin overview refresh error:', error);
                regions.forEach((region) => region.classList.remove('is-refreshing'));
            } finally {
                superAdminOverviewLoading = false;
                selectors.forEach((selector) => {
                    document.querySelector(selector)?.classList.remove('is-refreshing');
                });
            }
        }

        async function loadTopProductsRegion(url) {
            return loadSuperAdminOverviewRegions(url, {
                regionSelectors: ['[data-top-products-region]'],
            });
        }

        document.addEventListener('change', function (event) {
            const branchSelect = event.target && event.target.closest('[data-top-products-branch-select]');
            if (!branchSelect) {
                return;
            }

            const url = new URL(window.location.href);
            const branchId = branchSelect.value;
            if (branchId) {
                url.searchParams.set('top_product_branch_id', branchId);
            } else {
                url.searchParams.delete('top_product_branch_id');
            }

            loadTopProductsRegion(url.toString());
        });


        let quickTrendAbortController = null;
        let quickTrendRequestToken = 0;
        const quickTrendResponseCache = new Map();
        const QUICK_TREND_CACHE_TTL = 10000;

        function quickTrendUrl() {
            return new URL(window.location.href);
        }

        // Request riêng của biểu đồ chỉ mang đúng các tham số cần thiết.
        // Không gửi toàn bộ query string của dashboard để FormRequest khỏi validate
        // branch list, product state, pagination... không liên quan tới biểu đồ.
        function quickTrendRequestUrl(stateUrl) {
            const source = stateUrl instanceof URL ? stateUrl : new URL(stateUrl, window.location.origin);
            const requestUrl = new URL(window.location.pathname, window.location.origin);
            requestUrl.searchParams.set('quick_trend_json', '1');

            const period = source.searchParams.get('quick_trend_period') || 'week';
            requestUrl.searchParams.set('quick_trend_period', period);

            const branchId = source.searchParams.get('quick_trend_branch_id');
            if (branchId) requestUrl.searchParams.set('quick_trend_branch_id', branchId);

            if (period === 'range') {
                const start = source.searchParams.get('quick_trend_start_date');
                const end = source.searchParams.get('quick_trend_end_date');
                if (start) requestUrl.searchParams.set('quick_trend_start_date', start);
                if (end) requestUrl.searchParams.set('quick_trend_end_date', end);
            }

            return requestUrl;
        }

        function quickTrendCacheKey(stateUrl) {
            return quickTrendRequestUrl(stateUrl).searchParams.toString();
        }

        function syncQuickTrendHiddenFields(url) {
            const form = document.querySelector('[data-legacy-analytics-form]');
            if (!form) {
                return;
            }

            const names = [
                'quick_trend_period',
                'quick_trend_branch_id',
                'quick_trend_start_date',
                'quick_trend_end_date',
            ];
            names.forEach((name) => form.querySelectorAll(`input[name="${name}"]`).forEach((input) => input.remove()));

            const activePeriod = url.searchParams.get('quick_trend_period')
                || document.querySelector('[data-quick-trend-period].active')?.dataset.quickTrendPeriod
                || 'week';
            url.searchParams.set('quick_trend_period', activePeriod);

            names.forEach((name) => {
                const value = url.searchParams.get(name);
                if (!value) {
                    return;
                }
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
        }

        function setQuickTrendPeriodUi(period) {
            document.querySelectorAll('[data-quick-trend-period]').forEach((button) => {
                button.classList.toggle('active', button.dataset.quickTrendPeriod === period);
            });
        }

        function currentAppliedQuickTrendPeriod() {
            return new URL(window.location.href).searchParams.get('quick_trend_period') || 'week';
        }

        function openQuickTrendRangePopover() {
            const rangeForm = document.querySelector('[data-quick-trend-range-form]');
            if (!rangeForm) return;
            rangeForm.hidden = false;
            requestAnimationFrame(() => rangeForm.querySelector('[data-quick-trend-start-date]')?.focus({ preventScroll: true }));
        }

        function closeQuickTrendRangePopover({ restoreApplied = false } = {}) {
            const rangeForm = document.querySelector('[data-quick-trend-range-form]');
            if (rangeForm) rangeForm.hidden = true;
            if (restoreApplied) setQuickTrendPeriodUi(currentAppliedQuickTrendPeriod());
        }

        function formatQuickTrendMoney(value) {
            return `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;
        }

        function renderQuickTrendChart(payload) {
            const region = document.querySelector('[data-quick-branch-trend-region]');
            const body = region?.querySelector('.sa-revenue-trend-body');
            if (!region || !body) {
                return;
            }

            const buckets = Array.isArray(payload?.buckets) ? payload.buckets : [];
            body.replaceChildren();

            if (!buckets.length) {
                const empty = document.createElement('div');
                empty.className = 'sa-revenue-trend-empty';
                const icon = document.createElement('i');
                icon.className = 'bi bi-bar-chart';
                const text = document.createElement('span');
                text.textContent = 'Chưa có dữ liệu cho khoảng đã chọn.';
                empty.append(icon, text);
                body.appendChild(empty);
                return;
            }

            const chart = document.createElement('div');
            chart.className = 'sa-revenue-trend-chart';
            chart.setAttribute('aria-label', 'Biểu đồ cột doanh thu theo thời gian');
            const bars = document.createElement('div');
            bars.className = 'sa-revenue-trend-bars';
            if (buckets.length <= 4) {
                bars.classList.add('is-sparse');
            } else if (buckets.length > 14) {
                bars.classList.add('is-dense');
            }

            buckets.forEach((bucket) => {
                const revenue = Number(bucket?.revenue || 0);
                const height = Math.max(0, Math.min(100, Number(bucket?.height || 0)));
                const col = document.createElement('div');
                col.className = 'sa-revenue-trend-col';
                col.title = `${bucket?.label || ''}: ${formatQuickTrendMoney(revenue)} doanh thu`;

                const wrap = document.createElement('div');
                wrap.className = 'sa-revenue-trend-bar-wrap';
                const bar = document.createElement('span');
                bar.className = `sa-revenue-trend-bar${revenue <= 0 ? ' is-zero' : ''}`;
                bar.style.height = revenue > 0 ? `${height}%` : '0%';
                wrap.appendChild(bar);

                const label = document.createElement('span');
                label.className = 'sa-revenue-trend-label';
                label.textContent = bucket?.label || '';
                col.append(wrap, label);
                bars.appendChild(col);
            });

            chart.appendChild(bars);
            body.appendChild(chart);
        }

        function applyQuickTrendPayload(payload, cleanUrl) {
            const resolvedPeriod = payload?.period || cleanUrl.searchParams.get('quick_trend_period') || 'week';
            setQuickTrendPeriodUi(resolvedPeriod);
            renderQuickTrendChart(payload);

            if (resolvedPeriod === 'range') {
                const startInput = document.querySelector('[data-quick-trend-start-date]');
                const endInput = document.querySelector('[data-quick-trend-end-date]');
                if (startInput && payload?.start) startInput.value = payload.start;
                if (endInput && payload?.end) endInput.value = payload.end;
            }

            window.history.replaceState({}, '', cleanUrl.toString());
            syncQuickTrendHiddenFields(cleanUrl);
        }

        async function loadQuickBranchTrendRegion(url) {
            const cleanUrl = new URL(url, window.location.origin);
            cleanUrl.searchParams.delete('quick_trend_json');
            const requestUrl = quickTrendRequestUrl(cleanUrl);
            const cacheKey = quickTrendCacheKey(cleanUrl);
            const cached = quickTrendResponseCache.get(cacheKey);

            // Quay lại phạm vi vừa xem trong 10 giây: render ngay từ RAM, không chờ HTTP.
            if (cached && (Date.now() - cached.at) < QUICK_TREND_CACHE_TTL) {
                applyQuickTrendPayload(cached.payload, cleanUrl);
                return;
            }

            if (quickTrendAbortController) {
                quickTrendAbortController.abort();
            }
            quickTrendAbortController = new AbortController();
            const requestToken = ++quickTrendRequestToken;
            const region = document.querySelector('[data-quick-branch-trend-region]');
            region?.classList.add('is-refreshing');

            try {
                const response = await fetch(requestUrl.toString(), {
                    signal: quickTrendAbortController.signal,
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                if (requestToken !== quickTrendRequestToken) {
                    return;
                }

                quickTrendResponseCache.set(cacheKey, { at: Date.now(), payload });
                if (quickTrendResponseCache.size > 12) {
                    quickTrendResponseCache.delete(quickTrendResponseCache.keys().next().value);
                }

                applyQuickTrendPayload(payload, cleanUrl);
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    console.error('Quick revenue trend refresh error:', error);
                }
            } finally {
                if (requestToken === quickTrendRequestToken) {
                    region?.classList.remove('is-refreshing');
                }
            }
        }

        document.addEventListener('change', function (event) {
            const branchSelect = event.target && event.target.closest('[data-quick-trend-branch-select]');
            if (!branchSelect) {
                return;
            }

            const url = quickTrendUrl();
            if (branchSelect.value) {
                url.searchParams.set('quick_trend_branch_id', branchSelect.value);
            } else {
                url.searchParams.delete('quick_trend_branch_id');
            }

            syncQuickTrendHiddenFields(url);
            loadQuickBranchTrendRegion(url.toString());
        });

        document.addEventListener('click', function (event) {
            const periodButton = event.target && event.target.closest('[data-quick-trend-period]');
            if (!periodButton) {
                return;
            }

            event.preventDefault();
            const period = periodButton.dataset.quickTrendPeriod || 'week';
            setQuickTrendPeriodUi(period);

            const url = quickTrendUrl();
            url.searchParams.set('quick_trend_period', period);

            if (period === 'range') {
                openQuickTrendRangePopover();
                return;
            }

            closeQuickTrendRangePopover();
            url.searchParams.delete('quick_trend_start_date');
            url.searchParams.delete('quick_trend_end_date');
            syncQuickTrendHiddenFields(url);
            loadQuickBranchTrendRegion(url.toString());
        });

        document.addEventListener('click', function (event) {
            const closeButton = event.target && event.target.closest('[data-quick-trend-range-close], [data-quick-trend-range-cancel]');
            if (closeButton) {
                event.preventDefault();
                closeQuickTrendRangePopover({ restoreApplied: true });
                return;
            }

            const rangeForm = document.querySelector('[data-quick-trend-range-form]');
            if (!rangeForm || rangeForm.hidden) return;
            if (event.target.closest('[data-quick-trend-range-form], [data-quick-trend-period="range"]')) return;
            closeQuickTrendRangePopover({ restoreApplied: true });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            const rangeForm = document.querySelector('[data-quick-trend-range-form]');
            if (!rangeForm || rangeForm.hidden) return;
            closeQuickTrendRangePopover({ restoreApplied: true });
            document.querySelector('[data-quick-trend-period="range"]')?.focus({ preventScroll: true });
        });

        document.addEventListener('submit', function (event) {
            const rangeForm = event.target && event.target.closest('[data-quick-trend-range-form]');
            if (!rangeForm) {
                return;
            }

            event.preventDefault();
            const startInput = rangeForm.querySelector('[data-quick-trend-start-date]');
            const endInput = rangeForm.querySelector('[data-quick-trend-end-date]');
            if (!startInput?.value || !endInput?.value || startInput.value > endInput.value) {
                return;
            }

            const url = quickTrendUrl();
            url.searchParams.set('quick_trend_period', 'range');
            url.searchParams.set('quick_trend_start_date', startInput.value);
            url.searchParams.set('quick_trend_end_date', endInput.value);
            syncQuickTrendHiddenFields(url);

            // Phản hồi UI ngay: đóng popover trước, giữ biểu đồ cũ trong lúc JSON nhẹ tải về.
            closeQuickTrendRangePopover();
            loadQuickBranchTrendRegion(url.toString());
        });
    </script>

    <script>
        function syncLegacyAnalyticsPeriodFields() {
            const form = document.querySelector('[data-legacy-analytics-form]');
            const periodInput = form?.querySelector('[data-analytics-period-type-input]');
            if (!form || !periodInput) {
                return;
            }

            const periodType = periodInput.value || 'all';
            const presetValues = form.dataset || {};
            document.querySelectorAll('[data-analytics-period-preset]').forEach((button) => {
                const isActive = button.getAttribute('data-analytics-period-preset') === periodType;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            const compareSelector = document.querySelector('[data-analytics-compare-selector]');
            if (compareSelector) {
                const customOption = compareSelector.querySelector('option[value="custom"]');
                if (customOption) {
                    customOption.disabled = periodType === 'all';
                }
                if (periodType === 'all' && compareSelector.value !== 'none') {
                    compareSelector.value = 'none';
                }
            }

            document.querySelectorAll('[data-analytics-period-value]').forEach((input) => {
                const inputPeriod = input.getAttribute('data-analytics-period-value');
                const shouldEnable = inputPeriod === periodType;
                input.disabled = !shouldEnable;

                if (shouldEnable) {
                    const defaultValueMap = {
                        day: presetValues.analyticsDefaultDate,
                        week: presetValues.analyticsDefaultWeek,
                        month: presetValues.analyticsDefaultMonth,
                        year: presetValues.analyticsDefaultYear,
                    };
                    const defaultValue = defaultValueMap[inputPeriod];
                    if (defaultValue) {
                        input.value = defaultValue;
                    }
                }
            });

            const rangeInputs = form.querySelectorAll('[data-analytics-period-range-input]');
            rangeInputs.forEach((input) => {
                const shouldEnable = periodType === 'range';
                input.disabled = !shouldEnable;
                if (shouldEnable) {
                    if (!input.value) {
                        input.value = input.name === 'analytics_start_date'
                            ? (presetValues.analyticsDefaultRangeStart || '')
                            : (presetValues.analyticsDefaultRangeEnd || '');
                    }
                }
            });

            document.querySelectorAll('[data-analytics-period-group]').forEach((field) => {
                const shouldShow = field.getAttribute('data-analytics-period-group') === 'range' && periodType === 'range';
                field.classList.toggle('legacy-analytics-hidden', !shouldShow);
                field.querySelectorAll('input, select, textarea, button').forEach((control) => {
                    if (control.type !== 'hidden') {
                        control.disabled = !shouldShow;
                    }
                });
            });

            syncLegacyAnalyticsCompareFields();
        }

function syncLegacyAnalyticsCompareFields() {
            const form = document.querySelector('[data-legacy-analytics-form]');
            const compareSelector = form?.querySelector('[data-analytics-compare-selector]');
            const periodInput = form?.querySelector('[data-analytics-period-type-input]');

            if (!form || !compareSelector || !periodInput) {
                return;
            }

            const compareType = compareSelector.value || 'none';
            const periodType = periodInput.value || 'all';

            document.querySelectorAll('[data-analytics-compare-group]').forEach((field) => {
                const fieldPeriod = field.getAttribute('data-analytics-compare-group');
                const shouldShow = compareType === 'custom' && fieldPeriod === periodType;
                field.classList.toggle('legacy-analytics-hidden', !shouldShow);
                field.querySelectorAll('input, select, textarea, button').forEach((control) => {
                    if (control.type !== 'hidden') {
                        control.disabled = !shouldShow;
                    }
                });
            });
}

function buildFormSearchParams(form, submitter = null) {
    const params = new URLSearchParams();
    const formData = new FormData(form);
    const submitterName = submitter && submitter.name ? submitter.name : null;
    const isSubmitButton = submitter && ['submit', 'button'].includes((submitter.type || '').toLowerCase());

    for (const [key, value] of formData.entries()) {
        if (isSubmitButton && submitterName && key === submitterName) {
            continue;
        }

        params.append(key, value);
    }

    if (isSubmitButton && submitterName) {
        params.set(submitterName, submitter.value ?? '');
    }

    return params;
}

function normalizeLegacyAnalyticsForm(form) {
            if (!form) {
                return null;
            }

            const params = new URLSearchParams(new FormData(form));
            const periodType = params.get('analytics_period_type') || 'all';
            const compareType = params.get('analytics_compare_type') || 'none';
            const activePeriodKeys = {
                day: ['analytics_date'],
                week: ['analytics_week'],
                month: ['analytics_month'],
                year: ['analytics_year'],
                range: ['analytics_start_date', 'analytics_end_date'],
            };
            const activeCompareKeys = {
                day: ['analytics_compare_date'],
                week: ['analytics_compare_start_date', 'analytics_compare_end_date'],
                month: ['analytics_compare_month'],
                year: ['analytics_compare_year'],
                range: ['analytics_compare_start_date', 'analytics_compare_end_date'],
            };

            ['analytics_date', 'analytics_week', 'analytics_month', 'analytics_year', 'analytics_start_date', 'analytics_end_date'].forEach((key) => params.delete(key));
            ['analytics_compare_date', 'analytics_compare_month', 'analytics_compare_year', 'analytics_compare_start_date', 'analytics_compare_end_date'].forEach((key) => params.delete(key));

            (activePeriodKeys[periodType] || []).forEach((key) => {
                const control = form.querySelector(`[name="${key}"]`);
                if (control && control.value !== '') {
                    params.set(key, control.value);
                }
            });

            if (periodType === 'all') {
                params.set('analytics_compare_type', 'none');
            } else if (compareType === 'custom') {
                (activeCompareKeys[periodType] || []).forEach((key) => {
                    const control = form.querySelector(`[name="${key}"]`);
                    if (control && control.value !== '') {
                        params.set(key, control.value);
                    }
                });
            }

            params.set('branch_page', '1');

            return params;
        }

        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches('[data-analytics-compare-selector]')) {
                syncLegacyAnalyticsCompareFields();
            }
        });

        document.addEventListener('click', function (event) {
            const periodButton = event.target && event.target.closest('[data-analytics-period-preset]');
            if (!periodButton) {
                return;
            }

            const form = document.querySelector('[data-legacy-analytics-form]');
            const periodInput = form?.querySelector('[data-analytics-period-type-input]');
            if (!periodInput) {
                return;
            }

            periodInput.value = periodButton.getAttribute('data-analytics-period-preset') || 'all';
            syncLegacyAnalyticsPeriodFields();
        });

        document.addEventListener('click', function (event) {
            const resetLink = event.target && event.target.closest('.legacy-analytics-reset-btn');
            if (!resetLink || !resetLink.closest('[data-legacy-analytics-form]')) {
                return;
            }

            event.preventDefault();
            loadSuperAdminOverviewRegions(resetLink.getAttribute('href'));
        });

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || !form.matches('[data-legacy-analytics-form]')) {
                return;
            }

            event.preventDefault();
            syncLegacyAnalyticsPeriodFields();

            const params = normalizeLegacyAnalyticsForm(form);
            if (!params) {
                return;
            }

            const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
            url.search = params.toString();
            loadSuperAdminOverviewRegions(url.toString());
        });

        document.addEventListener('DOMContentLoaded', () => {
            syncLegacyAnalyticsPeriodFields();
            syncLegacyAnalyticsCompareFields();
        });
    </script>

    <section class="sa-section sa-business-section" aria-labelledby="business-analytics-title" data-business-analysis-region>
        <div class="sa-section-heading">
            <div>
                <p class="sa-section-kicker">Phân tích kinh doanh</p>
                <h2 class="sa-section-title" id="business-analytics-title">So sánh, đào sâu theo chi nhánh và theo sản phẩm</h2>
                <p class="sa-section-copy">Hai module analytics còn lại dùng chung payload hiện có và hiển thị bằng tab phía client.</p>
            </div>
        </div>

        <div class="sa-panel sa-analytics-shell" id="analytics-tabs-shell">
            <div class="sa-analytics-tabs" role="tablist" aria-label="Chuyển vùng phân tích kinh doanh">
                <button type="button" class="sa-analytics-tab active" data-analytics-tab="branch-ranking" role="tab" aria-selected="true">So sánh chi nhánh</button>
                <button type="button" class="sa-analytics-tab" data-analytics-tab="focus-product-section" role="tab" aria-selected="false">Một món bán tốt ở đâu?</button>
            </div>

            <div class="sa-analytics-panels">
                <div class="sa-analytics-panel" data-analytics-tab-panel="branch-ranking" role="tabpanel">
                    @include('admin.super-admin.partials.branch-ranking')
                </div>
                <div class="sa-analytics-panel" data-analytics-tab-panel="focus-product-section" role="tabpanel" hidden>
                    @include('admin.super-admin.partials.product-branch-performance')
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="createBranchModal" tabindex="-1" aria-labelledby="createBranchModalLabel" aria-hidden="true" data-auto-open="{{ old('form_type') === 'branch' ? 'true' : 'false' }}">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" method="POST" action="{{ route('admin.branches.store', [], false) }}" style="border:0;border-radius:8px;">
                @csrf
                <input type="hidden" name="form_type" value="branch">
                <input type="hidden" name="return_to" value="super-admin">
                <div class="modal-header">
                    <h2 class="modal-title fs-6 fw-bold" id="createBranchModalLabel">Thêm chi nhánh mới</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" role="alert" style="font-size: 0.75rem; margin-bottom: 1rem;">
                        <i class="bi bi-info-circle me-2"></i>Thêm chi nhánh trực tiếp từ trang quản trị cấp cao để quản lý tập trung.
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="branch_create_name">Tên chi nhánh <span class="text-danger">*</span></label>
                            <input id="branch_create_name" class="form-control @error('name', 'createBranch') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Nhập tên chi nhánh" required>
                            @error('name', 'createBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="branch_create_code">Mã chi nhánh <span class="text-danger">*</span></label>
                            <input id="branch_create_code" class="form-control @error('code', 'createBranch') is-invalid @enderror" name="code" value="{{ old('code') }}" placeholder="VD: CN1, CN2" required>
                            @error('code', 'createBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="branch_create_email">Email</label>
                            <input id="branch_create_email" class="form-control @error('email', 'createBranch') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="branch@example.com">
                            @error('email', 'createBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="branch_create_phone">Điện thoại</label>
                            <input id="branch_create_phone" class="form-control @error('phone', 'createBranch') is-invalid @enderror" type="text" name="phone" value="{{ old('phone') }}" placeholder="0123456789">
                            @error('phone', 'createBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" for="branch_create_address">Địa chỉ</label>
                        <textarea id="branch_create_address" class="form-control @error('address', 'createBranch') is-invalid @enderror" name="address" rows="2" placeholder="Nhập địa chỉ chi nhánh">{{ old('address') }}</textarea>
                        @error('address', 'createBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @include('admin.partials.branch-map-link', [
                        'pickerId' => 'super-admin-create-branch-map-link',
                        'label' => 'Link Google Maps',
                        'hint' => 'Dán link Google Maps có chứa tọa độ để lưu latitude/longitude cho chi nhánh.',
                        'addressTarget' => '#branch_create_address',
                        'latName' => 'latitude',
                        'lngName' => 'longitude',
                        'latValue' => old('latitude'),
                        'lngValue' => old('longitude'),
                        'errorBag' => 'createBranch',
                    ])
                    <div class="form-check mb-3 mt-3">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="branch_create_status" checked>
                        <label class="form-check-label" for="branch_create_status">
                            Kích hoạt chi nhánh này
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="gap: 0.75rem;">
                    <button type="button" class="sa-btn" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="sa-btn sa-btn-primary" style="min-width: 160px; background: var(--sa-green); color: #fff; border-color: var(--sa-green);">
                        <i class="bi bi-check-circle me-1"></i>Thêm chi nhánh
                    </button>
                </div>
            </form>
        </div>
    </div>

    <section class="sa-section sa-ops-grid" aria-labelledby="ops-title">
        <div class="sa-section-heading">
            <div>
                <p class="sa-section-kicker">Quản trị vận hành</p>
                <h2 class="sa-section-title" id="ops-title">Nhân sự quản trị và thao tác hệ thống</h2>
            </div>
        </div>

        <div id="admins-region" data-admins-region>
            <section class="sa-panel" id="admins">
                <div class="sa-panel-header">
                    <div><h2 class="sa-panel-title">Danh sách quản trị viên</h2><p class="sa-panel-note">{{ $adminUsers->total() }} tài khoản phù hợp</p></div>
                </div>
        <form class="sa-filter-form" method="GET" action="{{ route('admin.super-admin') }}" data-admins-filter-form>
            <input class="sa-control" type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm theo tên hoặc email" aria-label="Tìm theo tên hoặc email">
            <input type="hidden" name="analytics_period_type" value="{{ $analyticsContext->periodType }}">
            <input type="hidden" name="analytics_date" value="{{ request('analytics_date') }}">
            <input type="hidden" name="analytics_week" value="{{ request('analytics_week') }}">
            <input type="hidden" name="analytics_month" value="{{ request('analytics_month') }}">
            <input type="hidden" name="analytics_year" value="{{ request('analytics_year') }}">
            <input type="hidden" name="analytics_start_date" value="{{ request('analytics_start_date') }}">
            <input type="hidden" name="analytics_end_date" value="{{ request('analytics_end_date') }}">
            <select class="sa-control" name="role" aria-label="Lọc vai trò">
                <option value="all">Tất cả vai trò</option>
                <option value="super" @selected($filters['role'] === 'super')>Quản trị cấp cao</option>
                <option value="admin" @selected($filters['role'] === 'admin')>Quản trị hệ thống</option>
            </select>
            <select class="sa-control" name="status" aria-label="Lọc trạng thái">
                <option value="all">Tất cả trạng thái</option>
                <option value="active" @selected($filters['status'] === 'active')>Hoạt động</option>
                <option value="locked" @selected($filters['status'] === 'locked')>Đã khóa</option>
            </select>
            <select class="sa-control" name="created" aria-label="Lọc ngày tạo">
                <option value="all">Mọi thời điểm</option>
                <option value="today" @selected($filters['created'] === 'today')>Hôm nay</option>
                <option value="week" @selected($filters['created'] === 'week')>7 ngày qua</option>
                <option value="month" @selected($filters['created'] === 'month')>30 ngày qua</option>
            </select>
            <div class="sa-filter-actions">
                <button class="sa-btn sa-btn-primary" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
                <a class="sa-btn" href="{{ route('admin.super-admin', request()->except(['q', 'status', 'role', 'created'])) }}#admins" title="Xóa bộ lọc" data-admins-reset><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>

        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr><th>Quản trị viên</th><th>Vai trò</th><th>Nhánh quản lý</th><th>Lần đăng nhập cuối</th><th>Hiện diện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse($adminUsers as $adminUser)
                        @php
                            $isSuper = $adminUser->isSuperAdmin();
                            $isCurrentAccount = $adminUser->is(auth()->user());
                            $initials = collect(preg_split('/\s+/u', trim($adminUser->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
                            $minutesSinceLogin = $adminUser->last_login_at?->diffInMinutes(now());
                            $presence = $isCurrentAccount || ($minutesSinceLogin !== null && $minutesSinceLogin <= 5) ? 'online' : ($minutesSinceLogin !== null && $minutesSinceLogin <= 30 ? 'away' : 'offline');
                            $presenceLabel = ['online' => 'Online', 'away' => 'Away', 'offline' => 'Offline'][$presence];
                        @endphp
                        <tr data-admin-id="{{ $adminUser->id }}">
                            <td>
                                <div class="sa-admin-cell">
                                    <span class="sa-avatar">
                                        @if($adminUser->avatar)
                                            <img src="{{ str_starts_with($adminUser->avatar, 'http') ? $adminUser->avatar : asset('storage/'.ltrim($adminUser->avatar, '/')) }}" alt="{{ $adminUser->name }}">
                                        @else
                                            {{ $initials ?: 'AD' }}
                                        @endif
                                    </span>
                                    <div><div class="sa-admin-name">{{ $adminUser->name }}</div><div class="sa-admin-email">{{ $adminUser->email }}</div></div>
                                </div>
                            </td>
                            <td><span class="sa-role {{ $isSuper ? 'sa-role-super' : 'sa-role-admin' }}"><i class="bi {{ $isSuper ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>{{ $isSuper ? 'Quản trị cấp cao' : 'Quản trị hệ thống' }}</span></td>
                            <td data-admin-branch-cell="{{ $adminUser->id }}">
                                @if($adminUser->branch)
                                    <div style="font-size: 0.69rem; line-height: 1.4;" data-admin-branch-display>
                                        <div style="color: var(--sa-ink); font-weight: 700;" data-admin-branch-name>{{ $adminUser->branch->name }}</div>
                                        <div style="color: var(--sa-muted); font-size: 0.62rem;" data-admin-branch-code>{{ $adminUser->branch->code }}</div>
                                    </div>
                                @else
                                    <span style="color: var(--sa-muted); font-size: 0.69rem;" data-admin-branch-empty>Chưa gán</span>
                                @endif
                            </td>
                            <td>{{ $adminUser->last_login_at?->format('d/m/Y H:i') ?? ($isCurrentAccount ? 'Phiên hiện tại' : 'Chưa đăng nhập') }}</td>
                            <td><span class="sa-presence sa-presence-{{ $presence }}">{{ $presenceLabel }}</span></td>
                            <td><span class="sa-state {{ $adminUser->is_active ? 'sa-state-active' : 'sa-state-locked' }}">{{ $adminUser->is_active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                            <td>
                                <div class="sa-actions" @if($adminUser->isSuperAdmin()) data-admin-actions-locked="{{ $adminUser->id }}" @endif>
                                    @if($adminUser->isSuperAdmin())
                                        <span class="sa-action-btn sa-action-btn-disabled" aria-disabled="true" title="Không áp dụng cho quản trị cấp cao"><i class="bi bi-eye"></i></span>
                                    @elseif($adminUser->branch_id)
                                        <a class="sa-action-btn" href="{{ route('admin.preview-admin', ['branch_id' => $adminUser->branch_id]) }}" title="Vào trang admin của chi nhánh bằng quyền super admin"><i class="bi bi-eye"></i></a>
                                    @else
                                        <a class="sa-action-btn" href="{{ route('admin.users.show', $adminUser) }}" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    @endif
                                    <button
                                        class="sa-action-btn {{ $adminUser->isSuperAdmin() ? 'sa-action-btn-disabled' : '' }}"
                                        type="button"
                                        @if($adminUser->isSuperAdmin())
                                            disabled
                                            aria-disabled="true"
                                            title="Không áp dụng cho quản trị cấp cao"
                                        @else
                                            data-bs-toggle="modal"
                                            data-bs-target="#adminActionsModal{{ $adminUser->id }}"
                                            title="Thao tác"
                                        @endif
                                    ><i class="bi bi-gear"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Không có quản trị viên phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-pagination">
            <span>Hiển thị {{ $adminUsers->firstItem() ?? 0 }}-{{ $adminUsers->lastItem() ?? 0 }} / {{ $adminUsers->total() }}</span>
            <div class="sa-page-links" aria-label="Phân trang quản trị viên">
                <a class="sa-page-link {{ $adminUsers->onFirstPage() ? 'disabled' : '' }}" href="{{ $adminUsers->previousPageUrl() ?? '#' }}" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                @foreach(range(1, max(1, $adminUsers->lastPage())) as $page)
                    <a class="sa-page-link {{ $page === $adminUsers->currentPage() ? 'active' : '' }}" href="{{ $adminUsers->url($page) }}">{{ $page }}</a>
                @endforeach
            </div>
        </div>
            </section>

    <!-- Branch Edit Modals -->
    @foreach($adminUsers as $adminUser)

        <!-- Admin Actions Modal -->
        <div class="modal fade" id="adminActionsModal{{ $adminUser->id }}" tabindex="-1" aria-labelledby="adminActionsLabel{{ $adminUser->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminActionsLabel{{ $adminUser->id }}">Thao tác quản trị viên</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Admin Info -->
                        <div style="padding: 1rem 0; border-bottom: 1px solid #e1e6e4; margin-bottom: 1rem;">
                            @php
                                $initials = collect(preg_split('/\s+/u', trim($adminUser->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
                            @endphp
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #e7f7f2; color: #0d9373; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                    @if($adminUser->avatar)
                                        <img src="{{ str_starts_with($adminUser->avatar, 'http') ? $adminUser->avatar : asset('storage/'.ltrim($adminUser->avatar, '/')) }}" alt="{{ $adminUser->name }}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                    @else
                                        {{ $initials ?: 'AD' }}
                                    @endif
                                </div>
                                <div>
                                    <div style="color: #111827; font-weight: 700; font-size: 0.95rem;">{{ $adminUser->name }}</div>
                                    <div style="color: #6b7280; font-size: 0.85rem;">{{ $adminUser->email }}</div>
                                    <div style="margin-top: 0.3rem;">
                                        <span class="sa-role {{ $adminUser->isSuperAdmin() ? 'sa-role-super' : 'sa-role-admin' }}"><i class="bi {{ $adminUser->isSuperAdmin() ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>{{ $adminUser->isSuperAdmin() ? 'Quản trị cấp cao' : 'Quản trị hệ thống' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Info if exists -->
                            @if($adminUser->branch)
                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e1e6e4;">
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 800; margin-bottom: 0.4rem;">Nhánh quản lý</div>
                                    <div style="color: #111827; font-weight: 700;">{{ $adminUser->branch->name }}</div>
                                    <div style="color: #6b7280; font-size: 0.85rem;">{{ $adminUser->branch->code }}</div>
                                </div>
                            @endif
                        </div>

                        @php
                            $isSelf = $adminUser->is(auth()->user());
                            $isSuperAdminUser = $adminUser->isSuperAdmin();
                            // Tabs: Phân quyền + Gán nhánh (ẩn với SuperAdmin) + Khóa/Mở (ẩn với chính mình) + Lịch sử
                            $tabCount = 2; // Phân quyền + Lịch sử luôn có
                            if (!$isSuperAdminUser) $tabCount++; // Gán nhánh
                            if (!$isSelf) $tabCount++;            // Khóa/Mở
                            $loginHistories = $loginHistoryByAdmin->get($adminUser->id, collect());
                        @endphp

                        <!-- Horizontal Tab Navigation -->
                        <div style="display: grid; grid-template-columns: repeat({{ $tabCount }}, minmax(0, 1fr)); gap: 0; border-bottom: 2px solid #e1e6e4; margin-bottom: 1.5rem;">
                            <button class="admin-action-tab" data-tab="permissions" style="width: 100%; min-width: 0; height: 76px; padding: 0.7rem 0.55rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.88rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.2rem; white-space: normal; line-height: 1.2;" onclick="switchTab(event, 'permissions', {{ $adminUser->id }})">
                                <i class="bi bi-key"></i><span>Phân quyền</span>
                            </button>
                            @if(!$isSuperAdminUser)
                            <button class="admin-action-tab" data-tab="branch" style="width: 100%; min-width: 0; height: 68px; padding: 0.55rem 0.4rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.8rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.18rem; white-space: normal; line-height: 1.15;" onclick="switchTab(event, 'branch', {{ $adminUser->id }})">
                                <i class="bi bi-shop"></i><span>Gán nhánh</span>
                            </button>
                            @endif
                            @if(!$adminUser->is(auth()->user()))
                                <button class="admin-action-tab" data-tab="security" style="width: 100%; min-width: 0; height: 76px; padding: 0.7rem 0.55rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.88rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.2rem; white-space: normal; line-height: 1.2;" onclick="switchTab(event, 'security', {{ $adminUser->id }})">
                                    <i class="bi bi-lock"></i><span>Khóa/Mở khóa</span>
                                </button>
                            @endif
                            <button class="admin-action-tab" data-tab="login-history" style="width: 100%; min-width: 0; height: 76px; padding: 0.7rem 0.55rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.88rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.2rem; white-space: normal; line-height: 1.2;" onclick="switchTab(event, 'login-history', {{ $adminUser->id }})">
                                <i class="bi bi-clock-history"></i><span>Lịch sử đăng nhập</span>
                            </button>
                        </div>

                        <!-- Tab Contents -->
                        <!-- Tab: Phân quyền -->
                        <div class="admin-action-content" id="content-permissions-{{ $adminUser->id }}" style="display: block;">
                            <div style="display: grid; gap: 0.75rem;">
                                <div style="padding: 0.9rem; background: #f8faf9; border-radius: 10px;">
                                    <div style="font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.55rem;">Vai trò hiện tại</div>
                                    <div data-current-role>
                                        <span class="sa-role {{ $adminUser->isSuperAdmin() ? 'sa-role-super' : 'sa-role-admin' }}" style="font-size: 0.84rem;"><i class="bi {{ $adminUser->isSuperAdmin() ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>{{ $adminUser->isSuperAdmin() ? 'Quản trị cấp cao' : 'Quản trị hệ thống' }}</span>
                                    </div>
                                </div>


                                <form method="POST" action="{{ route('admin.super-admin.update-role', $adminUser) }}" class="admin-role-form" data-admin-id="{{ $adminUser->id }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom: 0.85rem;">
                                        <label style="display: block; font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.55rem;">Chọn vai trò mới</label>
                                        <select name="role_id" class="form-select" style="font-size: 0.95rem;">
                                            <option value="">-- Chọn vai trò --</option>
                                            <option value="2" @selected($adminUser->role_id === 2)>Quản trị hệ thống</option>
                                            <option value="3" @selected($adminUser->role_id === 3)>Quản trị cấp cao</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                        <i class="bi bi-check-lg" style="margin-right: 0.4rem;"></i>Lưu thay đổi
                                    </button>
                                </form>
                                <a href="{{ route('admin.users.edit', $adminUser) }}" class="btn btn-secondary btn-sm" style="width: 100%;">
                                    <i class="bi bi-arrow-up-right" style="margin-right: 0.4rem;"></i>Xem chi tiết phân quyền
                                </a>
                            </div>
                        </div>

                        <!-- Tab: Gán nhánh -->
                        @if(!$isSuperAdminUser)
                        <div class="admin-action-content" id="content-branch-{{ $adminUser->id }}" style="display: none;">
                            <div style="display: grid; gap: 0.75rem;">
                                <div style="padding: 0.9rem; background: #f8faf9; border-radius: 10px;">
                                    <div style="font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.55rem;">Chi nhánh hiện tại</div>
                                    <div data-current-branch style="color: #111827; font-weight: 600; font-size: 0.95rem;">
                                        @if($adminUser->branch)
                                            {{ $adminUser->branch->name }} ({{ $adminUser->branch->code }})
                                        @else
                                            Chưa gán
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.super-admin.update-branch', $adminUser) }}" class="admin-branch-form" data-admin-id="{{ $adminUser->id }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom: 0.85rem;">
                                        <label style="display: block; font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.55rem;">Chọn chi nhánh</label>
                                        <select name="branch_id" class="form-select" style="font-size: 0.95rem;">
                                            <option value="">-- Không gán --</option>
                                            @forelse($branches ?? [] as $branch)
                                                @php
                                                    $branchOwner = $branch->users->first();
                                                    $isOwnedByOtherAdmin = $branchOwner && $branchOwner->id !== $adminUser->id;
                                                @endphp
                                                <option value="{{ $branch->id }}" @selected($adminUser->branch_id === $branch->id) @disabled($isOwnedByOtherAdmin)>
                                                    {{ $branch->name }} ({{ $branch->code }})
                                                    @if($isOwnedByOtherAdmin)
                                                        - Đã gán cho {{ $branchOwner->name }}
                                                    @endif
                                                </option>
                                            @empty
                                                <option disabled>Không có chi nhánh</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                        <i class="bi bi-check-lg" style="margin-right: 0.4rem;"></i>Lưu thay đổi
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- Tab: Lịch sử đăng nhập -->
                        <div class="admin-action-content" id="content-login-history-{{ $adminUser->id }}" style="display: none;">
                            <div style="display: grid; gap: 0.75rem;">
                                <div style="padding: 0.9rem; background: #f8faf9; border-radius: 10px;">
                                    <div style="font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.45rem;">Đăng nhập gần nhất</div>
                                    <div style="color: #111827; font-weight: 700;">
                                        {{ $adminUser->last_login_at?->format('d/m/Y H:i') ?? 'Chưa đăng nhập' }}
                                    </div>
                                    <div style="color: #6b7280; font-size: 0.82rem;">
                                        {{ $adminUser->last_login_ip ?: 'Không có IP' }}
                                    </div>
                                </div>

                                <div style="padding: 0.75rem; background: #fff; border: 1px solid #e5ece9; border-radius: 6px;">
                                    <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                                        <div class="flex-grow-1" style="min-width: 220px;">
                                            <label class="form-label small fw-bold mb-1" style="font-size: 0.82rem; color: #6b7280; text-transform: uppercase;">Tìm theo ngày</label>
                                            <input type="date" class="form-control form-control-sm" data-login-history-date-input="{{ $adminUser->id }}" onchange="filterLoginHistory({{ $adminUser->id }})">
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearLoginHistoryFilter({{ $adminUser->id }})">
                                            Xóa lọc
                                        </button>
                                    </div>
                                    <div style="font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.8rem;">4 lần đăng nhập gần nhất trong 3 tháng</div>

                                    @if($loginHistories->isNotEmpty())
                                        <div style="display: grid; gap: 0.8rem;" data-login-history-list="{{ $adminUser->id }}">
                                            @foreach($loginHistories as $history)
                                                <div
                                                    style="display: flex; justify-content: space-between; gap: 0.75rem; padding: 0.8rem 0.9rem; background: #f8faf9; border-radius: 10px;"
                                                    data-login-history-row="{{ $adminUser->id }}"
                                                    data-login-date="{{ $history->created_at?->format('Y-m-d') }}"
                                                    data-default-visible="{{ $loop->index < 4 ? '1' : '0' }}"
                                                    @if($loop->index >= 4) class="d-none" @endif
                                                >
                                                    <div style="min-width: 0;">
                                                        <div style="color: #111827; font-weight: 700; font-size: 0.98rem;">{{ $history->created_at?->format('d/m/Y H:i') }}</div>
                                                        <div style="color: #6b7280; font-size: 0.86rem;">{{ $history->action }}</div>
                                                    </div>
                                                    <div style="color: #6b7280; font-size: 0.86rem; white-space: nowrap;">{{ $history->ip_address ?: 'Nội bộ' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div style="color: #6b7280; font-size: 0.85rem;">Chưa có lịch sử đăng nhập.</div>
                                    @endif
                                    <div class="d-none" data-login-history-empty="{{ $adminUser->id }}" style="color: #6b7280; font-size: 0.85rem; margin-top: 0.75rem;">Không tìm thấy lịch sử đăng nhập phù hợp.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Khóa/Mở khóa -->
                        @if(!$adminUser->is(auth()->user()))
                            <div class="admin-action-content" id="content-security-{{ $adminUser->id }}" style="display: none;">
                                <div style="display: grid; gap: 0.75rem;">
                                    <div style="padding: 0.75rem; background: #f8faf9; border-radius: 6px;">
                                        <div style="font-size: 0.82rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.3rem;">Trạng thái tài khoản</div>
                                        <div>
                                            <span class="sa-state {{ $adminUser->is_active ? 'sa-state-active' : 'sa-state-locked' }}" style="font-size: 0.75rem;">{{ $adminUser->is_active ? '✓ Hoạt động' : '✕ Đã khóa' }}</span>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $adminUser) }}" style="margin: 0;">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-{{ $adminUser->is_active ? 'danger' : 'success' }} btn-sm" type="submit" style="width: 100%;">
                                            <i class="bi {{ $adminUser->is_active ? 'bi-lock' : 'bi-unlock' }}" style="margin-right: 0.4rem;"></i>{{ $adminUser->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
        </div>
    </section>

    <section class="sa-section sa-health-section" aria-labelledby="health-title">
        <div class="sa-section-heading">
            <div>
                <p class="sa-section-kicker">Sức khỏe hệ thống</p>
                <h2 class="sa-section-title" id="health-title">Theo dõi log và trạng thái dịch vụ</h2>
                <p class="sa-section-copy">Đây là lớp thông tin cuối trang, ít cạnh tranh thị giác với KPI và analytics nhưng vẫn giữ nguyên dữ liệu.</p>
            </div>
        </div>

    <section class="sa-health-grid">
        <article class="sa-panel" id="audit">
            <div class="sa-panel-header"><div><h2 class="sa-panel-title">Nhật ký hệ thống</h2><p class="sa-panel-note">Sự kiện xác thực và quản trị gần nhất</p></div><i class="bi bi-journal-text text-success"></i></div>
            @forelse($activityLogs as $log)
                @if($loop->first)<div class="sa-activity-list">@endif
                    <div class="sa-activity">
                        <span class="sa-activity-icon"><i class="bi {{ $log->category === 'auth' ? 'bi-box-arrow-in-right' : ($log->category === 'security' ? 'bi-shield-exclamation' : 'bi-activity') }}"></i></span>
                        <p><strong>{{ $log->actor_name ?: 'Hệ thống' }}</strong> · {{ $log->action }}</p>
                        <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->ip_address ?: 'Nội bộ' }}</time>
                    </div>
                @if($loop->last)</div>@endif
            @empty
                <div class="sa-empty"><i class="bi bi-journal-check"></i><strong>Chưa có sự kiện hệ thống</strong></div>
            @endforelse
        </article>

        <aside class="sa-panel" id="health">
            <div class="sa-panel-header"><div><h2 class="sa-panel-title">Tình trạng hệ thống</h2><p class="sa-panel-note">Trạng thái dịch vụ có thể kiểm tra</p></div><i class="bi bi-heart-pulse text-success"></i></div>
            <div class="sa-health-list">
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-database"></i>Database</span><span class="sa-health-value {{ $systemHealth['database']['tone'] }}">{{ $systemHealth['database']['label'] }}</span></div>
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-device-ssd"></i>Storage</span><span class="sa-health-value">{{ $systemHealth['storage'] }}</span></div>
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-lightning"></i>Cache</span><span class="sa-health-value">{{ strtoupper($systemHealth['cache']) }}</span></div>
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-envelope"></i>Email</span><span class="sa-health-value">{{ $systemHealth['mail'] }}</span></div>
            </div>
        </aside>
    </section>
    </section>


</div>

<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true" data-auto-open="{{ $errors->createAdmin->any() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.super-admin.admins.store') }}" style="border:0;border-radius:8px;">
            @csrf
            <input type="hidden" name="form_type" value="admin">
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="createAdminModalLabel">Thêm tài khoản quản trị viên</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <div class="alert alert-info" role="alert" style="font-size: 0.75rem; margin-bottom: 1rem;">
                    <i class="bi bi-info-circle me-2"></i>Tạo admin sẽ tự tạo một nhánh quản lý tương ứng.
                </div>
                <div class="mb-3"><label class="form-label small fw-bold" for="admin_name">Họ và tên</label><input id="admin_name" class="form-control @error('name', 'createAdmin') is-invalid @enderror" name="name" value="{{ old('name') }}" required>@error('name', 'createAdmin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label small fw-bold" for="admin_email">Email</label><input id="admin_email" class="form-control @error('email', 'createAdmin') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required>@error('email', 'createAdmin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label small fw-bold" for="admin_password">Mật khẩu ban đầu</label><input id="admin_password" class="form-control @error('password', 'createAdmin') is-invalid @enderror" type="password" name="password" required>@error('password', 'createAdmin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6"><label class="form-label small fw-bold" for="admin_password_confirmation">Xác nhận mật khẩu</label><input id="admin_password_confirmation" class="form-control" type="password" name="password_confirmation" required></div>
                </div>
                <div class="form-check form-switch mt-3"><input type="hidden" name="is_active" value="0"><input id="admin_active" class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')><label class="form-check-label small fw-semibold" for="admin_active">Kích hoạt ngay</label></div>
            </div>
            <div class="modal-footer" style="gap: 0.75rem;background:#f9fafb;border-top:1px solid #e5e7eb;">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn px-4 fw-bold" style="min-width:160px;background:#0d9373;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-person-plus me-1"></i>Tạo Admin
                </button>
            </div>
        </form>
    </div>
</div>

@include('admin.partials.branch-map-link-script')

{{-- Modal tạo nhân viên (role_id = 5) --}}
<div class="modal fade" id="createStaffModal" tabindex="-1" aria-labelledby="createStaffModalLabel" aria-hidden="true"
     data-auto-open="{{ $errors->createStaff->any() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
        <form method="POST" action="{{ route('admin.super-admin.staff.store') }}">
            @csrf
            <input type="hidden" name="form_type" value="staff">
            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                <h2 class="modal-title fs-6 fw-bold" id="createStaffModalLabel">
                    <i class="bi bi-person-badge me-2" style="color:#d97706;"></i>Thêm nhân viên mới
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex gap-2 align-items-start" style="font-size:.76rem;border-radius:8px;">
                    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                    <div>
                        Nhân viên có quyền: <strong>Chat hỗ trợ</strong>, <strong>đổi trạng thái đơn hàng</strong> và <strong>đổi trạng thái đơn nhóm</strong> trong chi nhánh được gán.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold" for="staff_name">Họ và tên <span class="text-danger">*</span></label>
                    <input id="staff_name" class="form-control @error('name', 'createStaff') is-invalid @enderror"
                           name="name" value="{{ old('name') }}" required placeholder="Nguyễn Văn A">
                    @error('name', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold" for="staff_email">Email <span class="text-danger">*</span></label>
                    <input id="staff_email" class="form-control @error('email', 'createStaff') is-invalid @enderror"
                           type="email" name="email" value="{{ old('email') }}" required placeholder="nhanvien@chilldrink.com">
                    @error('email', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold" for="staff_branch">Chi nhánh phụ trách</label>
                    <select id="staff_branch" class="form-select" name="branch_id">
                        <option value="">-- Chưa gán chi nhánh --</option>
                        @foreach(\App\Models\Branch::active()->orderBy('name')->get() as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Nhân viên chỉ thấy đơn hàng của chi nhánh được gán.</div>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold" for="staff_password">Mật khẩu <span class="text-danger">*</span></label>
                        <input id="staff_password" class="form-control @error('password', 'createStaff') is-invalid @enderror"
                               type="password" name="password" required>
                        @error('password', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold" for="staff_password_confirmation">Xác nhận mật khẩu</label>
                        <input id="staff_password_confirmation" class="form-control" type="password" name="password_confirmation" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:.75rem 1rem;justify-content:flex-end;gap:.5rem;">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" id="createStaffSubmitBtn"
                    style="display:inline-flex;align-items:center;gap:6px;min-width:150px;font-weight:700;font-size:0.875rem;padding:0.5rem 1.25rem;border-radius:6px;border:none;outline:none;cursor:pointer;background-color:#d97706;color:#ffffff;line-height:1.5;"
                    onmouseover="this.style.backgroundColor='#b45309'"
                    onmouseout="this.style.backgroundColor='#d97706'"
                    onmousedown="this.style.backgroundColor='#92400e'"
                    onmouseup="this.style.backgroundColor='#b45309'">
                    <i class="bi bi-person-badge" style="color:#ffffff;font-size:1rem;"></i>
                    <span style="color:#ffffff;">Tạo Nhân Viên</span>
                </button>
            </div>
        </form>
        </div>
    </div>
</div>



@php
    $modalToOpen = old('form_type') === 'admin'
        ? 'createAdminModal'
        : (old('form_type') === 'staff'
            ? 'createStaffModal'
            : (old('form_type') === 'branch'
                ? 'createBranchModal'
                : (old('form_type') === 'branch-edit' && old('branch_modal_id') ? 'branchEditModal'.old('branch_modal_id') : null)));
@endphp

@if($modalToOpen)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById(@json($modalToOpen));
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });
</script>
@endif
@endsection

{{-- Fallback: xử lý data-auto-open cho tất cả modal (backup khi $modalToOpen không chạy) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.modal[data-auto-open="true"]').forEach(function (modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
});
</script>


<script>
function switchTab(event, tabName, adminId) {
    event.preventDefault();
    
    const modalId = 'adminActionsModal' + adminId;
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Hide all tab content for this admin
    const allContents = modal.querySelectorAll('[id^="content-"]');
    allContents.forEach(content => {
        content.style.display = 'none';
    });
    
    // Show the selected tab content
    const selectedContent = document.getElementById('content-' + tabName + '-' + adminId);
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }
    
    // Update tab button styles
    const tabs = modal.querySelectorAll('.admin-action-tab');
    tabs.forEach(tab => {
        if (tab.getAttribute('data-tab') === tabName) {
            tab.style.color = '#0d9373';
            tab.style.borderBottomColor = '#0d9373';
            tab.style.fontWeight = '700';
        } else {
            tab.style.color = '#6b7280';
            tab.style.borderBottomColor = 'transparent';
            tab.style.fontWeight = '600';
        }
    });
}

function renderLoginHistory(adminId) {
    const modal = document.getElementById('adminActionsModal' + adminId);
    if (!modal) return;

    const dateInput = modal.querySelector(`[data-login-history-date-input="${adminId}"]`);
    const emptyState = modal.querySelector(`[data-login-history-empty="${adminId}"]`);
    const rows = modal.querySelectorAll(`[data-login-history-row="${adminId}"]`);
    const selectedDate = dateInput ? dateInput.value : '';
    let visibleCount = 0;

    rows.forEach((row) => {
        const rowDate = row.getAttribute('data-login-date') || '';
        const defaultVisible = row.getAttribute('data-default-visible') === '1';
        const shouldShow = selectedDate ? rowDate === selectedDate : defaultVisible;

        row.classList.toggle('d-none', !shouldShow);

        if (shouldShow) {
            visibleCount += 1;
        }
    });

    if (emptyState) {
        emptyState.classList.toggle('d-none', visibleCount > 0);
    }
}

function filterLoginHistory(adminId) {
    renderLoginHistory(adminId);
}

function clearLoginHistoryFilter(adminId) {
    const modal = document.getElementById('adminActionsModal' + adminId);
    if (!modal) return;

    const dateInput = modal.querySelector(`[data-login-history-date-input="${adminId}"]`);
    if (dateInput) {
        dateInput.value = '';
    }

    renderLoginHistory(adminId);
}

function renderBranchStatusToggle(branchId) {
    const input = document.querySelector(`[data-branch-status-input="${branchId}"]`);
    const button = document.querySelector(`[data-branch-status-toggle="${branchId}"]`);

    if (!input || !button) {
        return;
    }

    const isActive = input.value === '1';
    button.classList.toggle('btn-success', isActive);
    button.classList.toggle('btn-danger', !isActive);
    button.innerHTML = `<i class="bi bi-${isActive ? 'toggle-on' : 'toggle-off'} me-1"></i><span data-branch-status-label="${branchId}">${isActive ? 'Đóng chi nhánh' : 'Mở chi nhánh'}</span>`;
}

function showSuperAdminToast(message, tone = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tone} alert-dismissible fade show`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="bi bi-${tone === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function setBranchEditErrors(form, errors) {
    const branchId = form.getAttribute('data-branch-id');
    const errorBox = form.querySelector(`[data-branch-edit-errors="${branchId}"]`);

    if (!errorBox) {
        return;
    }

    const messages = Object.values(errors || {}).flat().filter(Boolean);

    if (messages.length === 0) {
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
        return;
    }

    errorBox.classList.remove('d-none');
    errorBox.innerHTML = messages.map((message) => `<div>${message}</div>`).join('');
}

function clearBranchEditErrors(form) {
    setBranchEditErrors(form, {});
}

function syncBranchEditModal(form, branch) {
    const branchId = String(branch.id);
    const nameInput = form.querySelector(`#branch_name_${branchId}`);
    const codeInput = form.querySelector(`#branch_code_${branchId}`);
    const emailInput = form.querySelector(`#branch_email_${branchId}`);
    const phoneInput = form.querySelector(`#branch_phone_${branchId}`);
    const addressInput = form.querySelector(`#branch_address_${branchId}`);
    const mapLinkInput = form.querySelector(`[name="map_link"]`);
    const latitudeInput = form.querySelector(`[name="latitude"]`);
    const longitudeInput = form.querySelector(`[name="longitude"]`);
    const statusInput = form.querySelector(`[data-branch-status-input="${branchId}"]`);

    if (nameInput) nameInput.value = branch.name ?? '';
    if (codeInput) codeInput.value = branch.code ?? '';
    if (emailInput) emailInput.value = branch.email ?? '';
    if (phoneInput) phoneInput.value = branch.phone ?? '';
    if (addressInput) addressInput.value = branch.address ?? '';
    if (mapLinkInput) {
        mapLinkInput.value = branch.map_link ?? (branch.latitude !== null && branch.longitude !== null
            ? `https://www.google.com/maps?q=${branch.latitude},${branch.longitude}`
            : '');
    }
    if (latitudeInput) latitudeInput.value = branch.latitude ?? '';
    if (longitudeInput) longitudeInput.value = branch.longitude ?? '';
    if (statusInput) statusInput.value = branch.status ? '1' : '0';

    renderBranchStatusToggle(branchId);
}

function syncBranchRankingRow(branch) {
    const branchId = String(branch.id);
    const row = document.querySelector(`tr[data-branch-row="${branchId}"]`);

    if (!row) {
        return;
    }

    const nameCell = row.querySelector('[data-branch-name-cell]');
    if (nameCell) {
        nameCell.textContent = branch.name ?? '';
    }

    const statusCell = row.querySelector('[data-branch-status-cell]');
    if (statusCell) {
        statusCell.innerHTML = branch.status
            ? `<span class="sa-state sa-state-active" data-branch-status-badge="${branchId}"><i class="bi bi-check-circle"></i> Hoạt động</span>`
            : `<span class="sa-state" style="background: #fef2f2; color: #991b1b;" data-branch-status-badge="${branchId}"><i class="bi bi-pause-circle"></i> Tạm ngưng</span>`;
    }
}

function hideBranchEditModal(form) {
    const modal = form.closest('.modal');
    if (!modal) {
        return;
    }

    const instance = bootstrap.Modal.getInstance(modal);
    instance?.hide();
}

async function submitBranchEditForm(form) {
    const formData = new FormData(form);
    const action = form.getAttribute('action');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang lưu...';
    }

    clearBranchEditErrors(form);

    try {
        const response = await fetch(action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (data?.errors) {
                setBranchEditErrors(form, data.errors);
            }

            throw new Error(data?.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
        }

        const branch = data.branch || {};
        syncBranchEditModal(form, branch);
        syncBranchRankingRow(branch);
        hideBranchEditModal(form);
        showSuperAdminToast(data?.message || 'Cập nhật chi nhánh thành công!', 'success');
    } catch (error) {
        console.error('Branch edit error:', error);

        if (!form.querySelector('[data-branch-edit-errors]')) {
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

document.querySelectorAll('.branch-edit-form').forEach((form) => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        submitBranchEditForm(form);
    }, true);
});

document.addEventListener('click', function(e) {
    const toggleButton = e.target.closest('[data-branch-status-toggle]');
    if (!toggleButton) {
        return;
    }

    const branchId = toggleButton.getAttribute('data-branch-status-toggle');
    const input = document.querySelector(`[data-branch-status-input="${branchId}"]`);

    if (!input) {
        return;
    }

    input.value = input.value === '1' ? '0' : '1';
    renderBranchStatusToggle(branchId);
});

async function loadAdminsRegion(url) {
    const targetUrl = new URL(url, window.location.origin);
    targetUrl.hash = 'admins';
    const currentScrollTop = window.scrollY;

    try {
        const response = await fetch(targetUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const html = await response.text();
        const parser = new DOMParser();
        const nextDoc = parser.parseFromString(html, 'text/html');
        const nextRegion = nextDoc.querySelector('[data-admins-region]');
        const currentRegion = document.querySelector('[data-admins-region]');

        if (!nextRegion || !currentRegion) {
            throw new Error('Không tìm thấy vùng quản trị viên để cập nhật.');
        }

        currentRegion.replaceWith(nextRegion);
        window.history.replaceState({}, '', targetUrl.toString());
        restoreStableScroll(currentScrollTop);
    } catch (error) {
        console.error('Admins region load error:', error);
    }
}

let branchSectionLoading = false;

function resolveAnalyticsTabFromHash(hashValue = '') {
    const normalizedHash = String(hashValue || '').replace(/^#/, '');
    if (normalizedHash === 'focus-product-section') {
        return 'focus-product-section';
    }
    return 'branch-ranking';
}

function setAnalyticsTab(tabId, options = {}) {
    const targetTabId = resolveAnalyticsTabFromHash(tabId);
    const buttons = document.querySelectorAll('[data-analytics-tab]');
    const panels = document.querySelectorAll('[data-analytics-tab-panel]');

    buttons.forEach((button) => {
        const isActive = button.getAttribute('data-analytics-tab') === targetTabId;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panels.forEach((panel) => {
        const isActive = panel.getAttribute('data-analytics-tab-panel') === targetTabId;
        panel.hidden = !isActive;
    });

    if (options.updateHash === true) {
        const nextUrl = new URL(window.location.href);
        nextUrl.hash = targetTabId;
        window.history.replaceState({}, '', nextUrl.toString());
    }
}

function syncAnalyticsTabFromLocation() {
    setAnalyticsTab(window.location.hash || 'branch-ranking');
}

function syncLegacyTopProducts() {
    const list = document.querySelector('[data-top-products-list]');
    if (!list) {
        return;
    }

    const rows = Array.from(list.querySelectorAll('[data-top-product-row]')).map((row, index) => ({
        row,
        index,
        rankLabel: row.querySelector('[data-top-product-rank-label]'),
    }));

    rows.sort((a, b) => {
        const aQuantity = Number(a.row.dataset.topProductQuantity || 0);
        const bQuantity = Number(b.row.dataset.topProductQuantity || 0);
        const quantityDiff = bQuantity - aQuantity;
        return quantityDiff !== 0 ? quantityDiff : (a.index - b.index);
    });

    rows.forEach(({ row, rankLabel }, index) => {
        list.appendChild(row);
        row.dataset.topProductRank = String(index + 1);
        if (rankLabel) {
            rankLabel.textContent = String(index + 1);
        }
    });

    list.dataset.currentSort = 'quantity';
}

async function loadSuperAdminAnalyticsRegions(url, options = {}) {
    if (branchSectionLoading) {
        return;
    }

    branchSectionLoading = true;
    const currentScrollTop = window.scrollY;
    const targetUrl = new URL(url, window.location.origin);
    const targetHash = options.hash || targetUrl.hash || 'branch-ranking';
    targetUrl.hash = targetHash;
    const shouldPushState = options.pushState === true;
    const shouldUpdateHistory = options.updateHistory !== false;
    const regionSelectors = options.regionSelectors || [
        '[data-branch-ranking-region]',
        '[data-product-branch-performance-region]',
    ];
    const currentRegions = regionSelectors
        .map((selector) => document.querySelector(selector))
        .filter(Boolean);

    currentRegions.forEach((region) => region.classList.add('branch-product-loading'));

    try {
        const response = await fetch(targetUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const html = await response.text();
        const parser = new DOMParser();
        const nextDoc = parser.parseFromString(html, 'text/html');
        let replaced = false;

        regionSelectors.forEach((selector) => {
            const nextRegion = nextDoc.querySelector(selector);
            const currentRegion = document.querySelector(selector);

            if (nextRegion && currentRegion) {
                currentRegion.replaceWith(nextRegion);
                replaced = true;
            }
        });

        if (!replaced) {
            throw new Error('Không tìm thấy vùng phân tích để cập nhật.');
        }

        if (shouldUpdateHistory) {
            if (shouldPushState) {
                window.history.pushState({}, '', targetUrl.toString());
            } else {
                window.history.replaceState({}, '', targetUrl.toString());
            }
        }

        syncAnalyticsTabFromLocation();
        restoreStableScroll(currentScrollTop);
    } catch (error) {
        console.error('Analytics region load error:', error);
    } finally {
        branchSectionLoading = false;
        regionSelectors.forEach((selector) => {
            document.querySelector(selector)?.classList.remove('branch-product-loading');
        });
    }
}

async function loadBranchComparisonRegion(url, options = {}) {
    return loadSuperAdminAnalyticsRegions(url, {
        ...options,
        hash: 'branch-ranking',
        regionSelectors: ['[data-branch-ranking-region]'],
    });
}

async function loadBranchRankingRegion(url, options = {}) {
    return loadSuperAdminAnalyticsRegions(url, {
        ...options,
        hash: 'branch-product-detail',
        regionSelectors: ['[data-branch-product-detail-region]'],
    });
}

async function loadBranchTimeComparisonRegion(url, options = {}) {
    return loadSuperAdminAnalyticsRegions(url, {
        ...options,
        hash: 'branch-time-matrix',
        regionSelectors: ['[data-branch-time-comparison-region]'],
    });
}

async function loadFocusProductRegion(url, options = {}) {
    return loadSuperAdminAnalyticsRegions(url, {
        ...options,
        hash: 'focus-product-section',
        regionSelectors: ['[data-product-branch-performance-region]'],
    });
}

// Initialize first tab as active on modal open
document.addEventListener('show.bs.modal', function(e) {
    if (e.target && e.target.id.startsWith('adminActionsModal')) {
        const adminId = e.target.id.replace('adminActionsModal', '');
        const firstTab = e.target.querySelector('.admin-action-tab[data-tab="permissions"]');
        if (firstTab) {
            // Simulate click on the first tab
            firstTab.click();
        }
        renderLoginHistory(adminId);
        return;
    }

    if (e.target && e.target.id.startsWith('branchEditModal')) {
        const branchId = e.target.id.replace('branchEditModal', '');
        renderBranchStatusToggle(branchId);
    }
});

document.addEventListener('submit', function(e) {
    if (e.target && e.target.matches('[data-admins-filter-form]')) {
        e.preventDefault();

        const form = e.target;
        const params = buildFormSearchParams(form, e.submitter || null);
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        url.search = params.toString();

        loadAdminsRegion(url.toString());
        return;
    }

    if (e.target && e.target.matches('[data-branch-ranking-form]')) {
        e.preventDefault();

        const form = e.target;
        const params = buildFormSearchParams(form, e.submitter || null);
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        url.search = params.toString();

        if (form.closest('[data-branch-ranking-region]')) {
            loadBranchComparisonRegion(url.toString());
            return;
        }

        loadBranchRankingRegion(url.toString());
        return;
    }

    if (e.target && e.target.matches('[data-branch-time-matrix-form]')) {
        e.preventDefault();

        const form = e.target;
        const params = buildFormSearchParams(form, e.submitter || null);
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        url.search = params.toString();

        loadBranchTimeComparisonRegion(url.toString());
        return;
    }

    if (e.target && e.target.matches('[data-product-branch-performance-form]')) {
        e.preventDefault();

        const form = e.target;
        const params = buildFormSearchParams(form, e.submitter || null);
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        url.search = params.toString();

        loadFocusProductRegion(url.toString());
    }
});

document.addEventListener('click', function(e) {
    const branchDetailLink = e.target.closest('[data-branch-detail-link]');
    if (branchDetailLink) {
        e.preventDefault();
        loadBranchRankingRegion(branchDetailLink.getAttribute('href'), { pushState: true });
        return;
    }

    const rankingLink = e.target.closest('[data-ranking-period]');
    if (rankingLink) {
        e.preventDefault();
        if (rankingLink.closest('[data-branch-ranking-region]')) {
            loadBranchComparisonRegion(rankingLink.getAttribute('href'));
            return;
        }

        loadBranchRankingRegion(rankingLink.getAttribute('href'));
        return;
    }

    const analyticsTabButton = e.target.closest('[data-analytics-tab]');
    if (analyticsTabButton) {
        e.preventDefault();
        setAnalyticsTab(analyticsTabButton.getAttribute('data-analytics-tab') || 'branch-ranking', { updateHash: true });
        return;
    }

    const focusProductLink = e.target.closest('[data-focus-product-link]');
    if (focusProductLink) {
        e.preventDefault();
        loadFocusProductRegion(focusProductLink.getAttribute('href'), { pushState: true });
        return;
    }

    const focusSortLink = e.target.closest('[data-focus-product-sort]');
    if (focusSortLink) {
        e.preventDefault();
        loadFocusProductRegion(focusSortLink.getAttribute('href'), { pushState: true });
        return;
    }


    const resetLink = e.target.closest('[data-admins-reset]');
    if (resetLink) {
        e.preventDefault();
        loadAdminsRegion(resetLink.getAttribute('href'));
        return;
    }

    const pageLink = e.target.closest('.sa-page-link');
    if (!pageLink) {
        return;
    }

    const timeMatrixRegion = pageLink.closest('[data-branch-time-comparison-region]');
    if (timeMatrixRegion) {
        if (pageLink.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        loadBranchTimeComparisonRegion(pageLink.getAttribute('href'));
        return;
    }

    const branchRegion = pageLink.closest('[data-branch-product-detail-region]');
    if (branchRegion) {
        if (pageLink.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        loadBranchRankingRegion(pageLink.getAttribute('href'));
        return;
    }

    const branchComparisonRegion = pageLink.closest('[data-branch-ranking-region]');
    if (branchComparisonRegion) {
        if (pageLink.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        loadBranchComparisonRegion(pageLink.getAttribute('href'));
        return;
    }

    const focusRegion = pageLink.closest('[data-product-branch-performance-region]');
    if (focusRegion) {
        if (pageLink.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        loadFocusProductRegion(pageLink.getAttribute('href'));
        return;
    }

    const adminsRegion = pageLink.closest('[data-admins-region]');
    if (!adminsRegion) {
        return;
    }

    if (pageLink.classList.contains('disabled')) {
        e.preventDefault();
        return;
    }

    e.preventDefault();
    loadAdminsRegion(pageLink.getAttribute('href'));
});

window.addEventListener('popstate', function() {
    if (document.querySelector('[data-branch-ranking-region]') || document.querySelector('[data-branch-time-comparison-region]') || document.querySelector('[data-product-branch-performance-region]')) {
        loadSuperAdminAnalyticsRegions(window.location.href, { updateHistory: false });
        return;
    }

    syncAnalyticsTabFromLocation();
});

window.addEventListener('hashchange', function() {
    syncAnalyticsTabFromLocation();
});

function initBranchScopeControl(root) {
    if (!root || root.dataset.branchScopeInitialized === '1') {
        return;
    }

    root.dataset.branchScopeInitialized = '1';
    syncBranchScope(root);
    filterBranchScopeOptions(root);
}

function getBranchScopeRefs(root) {
    if (!root) {
        return null;
    }

    return {
        root,
        trigger: root.querySelector('[data-branch-scope-trigger]'),
        panel: root.querySelector('[data-branch-scope-panel]'),
        search: root.querySelector('[data-branch-scope-search]'),
        selectAllButton: root.querySelector('[data-branch-scope-select-all]'),
        clearButton: root.querySelector('[data-branch-scope-clear]'),
        chips: root.querySelector('[data-branch-scope-chips]'),
        summary: root.querySelector('[data-branch-scope-summary]'),
        triggerText: root.querySelector('[data-branch-scope-trigger-text]'),
        triggerCount: root.querySelector('[data-branch-scope-trigger-count]'),
        hiddenContainer: root.querySelector('[data-branch-scope-hidden]'),
        checkboxItems: Array.from(root.querySelectorAll('[data-branch-scope-checkbox]')),
        optionItems: Array.from(root.querySelectorAll('[data-branch-scope-option]')),
        emptyState: root.querySelector('[data-branch-scope-empty-state]'),
        inputName: root.dataset.inputName || 'analytics_branch_ids',
        mirrorInputName: root.dataset.mirrorInputName || '',
        emptyLabel: root.dataset.emptyLabel || 'Tất cả chi nhánh',
    };
}

function getBranchScopeLabelMap(root) {
    if (!root) {
        return new Map();
    }

    if (!root.__branchScopeLabelMap) {
        root.__branchScopeLabelMap = new Map();
    }

    const labelMap = root.__branchScopeLabelMap;
    labelMap.clear();

    root.querySelectorAll('[data-branch-scope-option]').forEach((option) => {
        const checkbox = option.querySelector('[data-branch-scope-checkbox]');
        const title = option.querySelector('.sa-branch-scope-option-title')?.textContent?.trim() || `Chi nhánh #${checkbox?.value || ''}`;
        labelMap.set(Number(checkbox?.value || 0), title);
    });

    return labelMap;
}

function closeBranchScope(root, options = {}) {
    const refs = getBranchScopeRefs(root);
    if (!refs?.panel) {
        return;
    }

    refs.panel.hidden = true;
    refs.panel.classList.remove('align-right');
    refs.panel.style.maxHeight = '';
    if (refs.trigger) {
        refs.trigger.setAttribute('aria-expanded', 'false');
        if (options.focusTrigger) {
            refs.trigger.focus();
        }
    }
}

function positionBranchScopePanel(root) {
    const refs = getBranchScopeRefs(root);
    if (!refs?.panel || refs.panel.hidden) {
        return;
    }

    refs.panel.classList.remove('align-right');
    refs.panel.style.maxHeight = '';

    const rootRect = root.getBoundingClientRect();
    const panelRect = refs.panel.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const availableHeight = Math.max(220, viewportHeight - rootRect.bottom - 24);

    if (panelRect.right > viewportWidth - 16) {
        refs.panel.classList.add('align-right');
    }

    refs.panel.style.maxHeight = `${Math.min(420, availableHeight)}px`;
}

function openBranchScope(root) {
    const refs = getBranchScopeRefs(root);
    if (!refs?.panel) {
        return;
    }

    document.querySelectorAll('[data-branch-scope]').forEach((candidate) => {
        if (candidate !== root) {
            closeBranchScope(candidate);
        }
    });

    refs.panel.hidden = false;
    if (refs.trigger) {
        refs.trigger.setAttribute('aria-expanded', 'true');
    }

    positionBranchScopePanel(root);
    refs.search?.focus();
}

function syncBranchScope(root) {
    const refs = getBranchScopeRefs(root);
    if (!refs) {
        return;
    }

    const labelMap = getBranchScopeLabelMap(root);
    const selected = refs.checkboxItems
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => Number(checkbox.value))
        .filter((id) => Number.isFinite(id) && id > 0);

    if (refs.hiddenContainer) {
        refs.hiddenContainer.innerHTML = selected.map((id) => {
            let html = `<input type="hidden" name="${refs.inputName}[]" value="${id}">`;
            if (refs.mirrorInputName) {
                html += `<input type="hidden" name="${refs.mirrorInputName}[]" value="${id}">`;
            }
            return html;
        }).join('');
    }

    const selectedLabels = selected.map((id) => labelMap.get(id) || `Chi nhánh #${id}`);
    const selectedCount = selected.length;
    const scopeLabel = selectedCount === 1
        ? selectedLabels[0]
        : (selectedCount > 1 ? `Đã chọn ${selectedCount} chi nhánh` : refs.emptyLabel);

    if (refs.summary) {
        refs.summary.textContent = scopeLabel;
    }

    if (refs.triggerText) {
        refs.triggerText.textContent = scopeLabel;
    }

    if (refs.triggerCount) {
        refs.triggerCount.textContent = selectedCount > 0 ? String(selectedCount) : 'Tất cả';
    }

    if (refs.chips) {
        refs.chips.innerHTML = '';

        if (selectedCount === 0) {
            refs.chips.insertAdjacentHTML('beforeend', '<span class="sa-branch-scope-chip muted">Tất cả chi nhánh</span>');
        } else {
            selectedLabels.slice(0, 4).forEach((label) => {
                refs.chips.insertAdjacentHTML('beforeend', `<span class="sa-branch-scope-chip">${label}</span>`);
            });

            if (selectedCount > 4) {
                refs.chips.insertAdjacentHTML('beforeend', `<span class="sa-branch-scope-chip muted">+${selectedCount - 4} chi nhánh</span>`);
            }
        }
    }
}

function filterBranchScopeOptions(root) {
    const refs = getBranchScopeRefs(root);
    if (!refs) {
        return;
    }

    const keyword = (refs.search?.value || '').trim().toLowerCase();
    let visibleCount = 0;

    refs.optionItems.forEach((option) => {
        const searchText = (option.dataset.branchScopeSearchText || '').toLowerCase();
        const isVisible = keyword === '' || searchText.includes(keyword);
        option.hidden = !isVisible;
        if (isVisible) {
            visibleCount += 1;
        }
    });

    if (refs.emptyState) {
        refs.emptyState.hidden = visibleCount > 0;
    }
}

document.querySelectorAll('[data-branch-scope]').forEach(initBranchScopeControl);
syncAnalyticsTabFromLocation();
syncLegacyTopProducts();

if (!window.__branchScopeGlobalListenersBound) {
    window.__branchScopeGlobalListenersBound = true;

    document.addEventListener('click', function(event) {
        const trigger = event.target.closest('[data-branch-scope-trigger]');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();

            const root = trigger.closest('[data-branch-scope]');
            const panel = root?.querySelector('[data-branch-scope-panel]');
            if (!root || !panel) {
                return;
            }

            if (panel.hidden) {
                openBranchScope(root);
            } else {
                closeBranchScope(root, { focusTrigger: true });
            }
            return;
        }

        const closeButton = event.target.closest('[data-branch-scope-close]');
        if (closeButton) {
            event.preventDefault();
            const root = closeButton.closest('[data-branch-scope]');
            closeBranchScope(root, { focusTrigger: true });
            return;
        }

        const selectAllButton = event.target.closest('[data-branch-scope-select-all]');
        if (selectAllButton) {
            event.preventDefault();
            const root = selectAllButton.closest('[data-branch-scope]');
            const refs = getBranchScopeRefs(root);
            refs?.checkboxItems.forEach((checkbox) => {
                checkbox.checked = true;
            });
            syncBranchScope(root);
            return;
        }

        const clearButton = event.target.closest('[data-branch-scope-clear]');
        if (clearButton) {
            event.preventDefault();
            const root = clearButton.closest('[data-branch-scope]');
            const refs = getBranchScopeRefs(root);
            refs?.checkboxItems.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncBranchScope(root);
            return;
        }

        document.querySelectorAll('[data-branch-scope]').forEach(function(root) {
            const panel = root.querySelector('[data-branch-scope-panel]');
            if (panel && panel.hidden === false && !root.contains(event.target)) {
                closeBranchScope(root);
            }
        });
    });

    document.addEventListener('input', function(event) {
        const search = event.target.closest('[data-branch-scope-search]');
        if (!search) {
            return;
        }

        filterBranchScopeOptions(search.closest('[data-branch-scope]'));
    });

    document.addEventListener('change', function(event) {
        const checkbox = event.target.closest('[data-branch-scope-checkbox]');
        if (!checkbox) {
            return;
        }

        syncBranchScope(checkbox.closest('[data-branch-scope]'));
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            let closedAny = false;

            document.querySelectorAll('[data-branch-scope]').forEach(function(root) {
                const panel = root.querySelector('[data-branch-scope-panel]');
                if (panel && panel.hidden === false) {
                    closeBranchScope(root, { focusTrigger: !closedAny });
                    closedAny = true;
                }
            });
            return;
        }

        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const trigger = event.target.closest('[data-branch-scope-trigger]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        trigger.click();
    });

    window.addEventListener('resize', function() {
        document.querySelectorAll('[data-branch-scope]').forEach(positionBranchScopePanel);
    });
}

// AJAX form submission for role and branch assignment
document.addEventListener('submit', async function(e) {
    const form = e.target;
    const isRoleForm = form.classList.contains('admin-role-form');
    const isAdminBranchForm = form.classList.contains('admin-branch-form');

    if (!isRoleForm && !isAdminBranchForm) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const formData = new FormData(form);
    const action = form.getAttribute('action');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    const adminId = form.getAttribute('data-admin-id');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang lưu...';
    }

    try {
        const response = await fetch(action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data?.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
        }

        const successMessage = isRoleForm
            ? 'Đã cập nhật vai trò thành công'
            : 'Đã cập nhật chi nhánh thành công';

        showSuperAdminToast(successMessage, 'success');

        if (isRoleForm) {
            const currentRoleDisplay = form.closest('.modal-body').querySelector('[data-current-role]');
            const selectedOption = form.querySelector('select[name="role_id"] option:checked');
            const newRoleLabel = selectedOption.textContent;
            const roleId = form.querySelector('select[name="role_id"]').value;
            const isSuperAdmin = roleId === '3';

            if (currentRoleDisplay) {
                currentRoleDisplay.innerHTML = `
                    <span class="sa-role ${isSuperAdmin ? 'sa-role-super' : 'sa-role-admin'}" style="font-size: 0.75rem;">
                        <i class="bi ${isSuperAdmin ? 'bi-shield-fill-check' : 'bi-gear'}"></i>
                        ${newRoleLabel}
                    </span>
                `;
            }

            const tableRow = document.querySelector(`tr[data-admin-id="${adminId}"]`);
            if (tableRow) {
                const roleCell = tableRow.querySelector('td:nth-child(2)');
                if (roleCell) {
                    roleCell.innerHTML = `
                        <span class="sa-role ${isSuperAdmin ? 'sa-role-super' : 'sa-role-admin'}">
                            <i class="bi ${isSuperAdmin ? 'bi-shield-fill-check' : 'bi-gear'}"></i>
                            ${newRoleLabel}
                        </span>
                    `;
                }
            }
        } else if (isAdminBranchForm) {
            const selectedOption = form.querySelector('select[name="branch_id"] option:checked');
            const selectedBranchId = form.querySelector('select[name="branch_id"]').value;
            const newBranchLabel = selectedBranchId
                ? (selectedOption?.textContent?.trim() || 'Chưa gán')
                : 'Chưa gán';
            const currentBranchDisplay = form.closest('.modal-body').querySelector('[data-current-branch]');
            const tableRow = document.querySelector(`tr[data-admin-id="${adminId}"]`);

            if (currentBranchDisplay) {
                currentBranchDisplay.textContent = newBranchLabel;
            }

            if (tableRow) {
                const branchCell = tableRow.querySelector('[data-admin-branch-cell]');

                if (branchCell) {
                    if (selectedBranchId) {
                        const branchMatch = newBranchLabel.match(/^(.*)\s+\((.*)\)$/);
                        const branchName = branchMatch ? branchMatch[1] : newBranchLabel;
                        const branchCode = branchMatch ? branchMatch[2] : '';

                        branchCell.innerHTML = `
                            <div style="font-size: 0.69rem; line-height: 1.4;" data-admin-branch-display>
                                <div style="color: var(--sa-ink); font-weight: 700;" data-admin-branch-name>${branchName}</div>
                                <div style="color: var(--sa-muted); font-size: 0.62rem;" data-admin-branch-code>${branchCode}</div>
                            </div>
                        `;
                    } else {
                        branchCell.innerHTML = '<span style="color: var(--sa-muted); font-size: 0.69rem;" data-admin-branch-empty>Chưa gán</span>';
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error:', error);

        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
});
</script>
