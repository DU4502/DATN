@extends('layouts.shipper')

@section('title', 'Lịch sử giao hàng')
@section('mobile-title', 'Lịch sử')
@section('mobile-subtitle', 'Các chuyến đã hoàn thành')

@section('content')
<div class="ship-page-head">
    <div>
        <h1>Lịch sử giao hàng</h1>
        <p>Xem lại đơn đã giao, thời gian hoàn tất và thu nhập phí giao.</p>
    </div>
    <span class="ship-head-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
</div>

@php
    $periodTabs = [
        'day' => 'Ngày',
        'week' => 'Tuần',
        'month' => 'Tháng',
        'year' => 'Năm',
    ];
@endphp

<section class="ship-income-panel">
    <div class="ship-income-head">
        <div>
            <h2>Thu nhập phí giao</h2>
            <p>Chỉ tính các đơn đã giao/hoàn thành.</p>
        </div>
        <i class="fa-solid fa-chart-line"></i>
    </div>

    <div class="ship-income-summary">
        @foreach($incomeSummary ?? [] as $key => $summary)
            <a href="{{ route('shipper.history', ['income_period' => $key]) }}"
               class="ship-income-card {{ ($incomePeriod ?? 'day') === $key ? 'is-active' : '' }}">
                <span>{{ $summary['label'] }}</span>
                <strong>{{ number_format((int) ($summary['amount'] ?? 0), 0, ',', '.') }}đ</strong>
                <em>{{ (int) ($summary['orders'] ?? 0) }} đơn</em>
            </a>
        @endforeach
    </div>

    <div class="ship-income-detail">
        <div class="ship-income-tabs">
            @foreach($periodTabs as $key => $label)
                <a href="{{ route('shipper.history', ['income_period' => $key]) }}"
                   class="{{ ($incomePeriod ?? 'day') === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="ship-income-rows">
            @forelse($incomeDetail ?? collect() as $row)
                <div class="ship-income-row">
                    <span>{{ $row['label'] }}</span>
                    <b>{{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}đ</b>
                    <em>{{ (int) ($row['orders'] ?? 0) }} đơn</em>
                </div>
            @empty
                <div class="ship-income-empty">Chưa có thu nhập trong khoảng này.</div>
            @endforelse
        </div>
    </div>
</section>

<div class="ship-order-list">
    @forelse($orders as $order)
        @php
            $normalized = \App\Support\OrderStatus::normalize((string)$order->status);
            $label = \App\Support\OrderStatus::label((string)$order->status);
            $phone = $order->customerPhone();
        @endphp
        <article class="ship-order-card">
            <div class="ship-order-top">
                <div>
                    <div class="ship-order-code">{{ $order->displayCode() }}</div>
                    <div class="ship-order-time">{{ $order->updated_at?->format('d/m/Y · H:i') }}</div>
                </div>
                <span class="ship-badge success"><i class="fa-solid fa-circle-check me-1"></i>{{ $label }}</span>
            </div>

            <div class="ship-order-customer">
                <span class="mini-avatar"><i class="fa-solid fa-user"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <b>{{ $order->customerName() ?: 'Khách hàng' }}</b>
                    <span>{{ $phone ?: 'Không có số điện thoại' }}</span>
                </div>
            </div>

            <div class="ship-address"><i class="fa-solid fa-location-dot"></i><span>{{ $order->getShippingAddress() }}</span></div>

            <div class="ship-order-actions one">
                <a href="{{ route('shipper.orders.show', $order->id) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-receipt me-1"></i>Xem chi tiết đơn</a>
            </div>
        </article>
    @empty
        <div class="ship-empty"><i class="fa-solid fa-clock-rotate-left"></i><b>Chưa có lịch sử giao hàng</b><p>Các chuyến giao hoàn tất sẽ được lưu tại đây.</p></div>
    @endforelse
</div>

@if($orders->hasPages())
    <div class="ship-pagination">{{ $orders->appends(['income_period' => $incomePeriod ?? 'day'])->links('pagination::bootstrap-5') }}</div>
@endif
@endsection

@push('styles')
<style>
    .ship-income-panel {
        background:#fff;
        border:1px solid var(--ship-line);
        border-radius:22px;
        padding:14px;
        margin-bottom:12px;
        box-shadow:0 8px 26px rgba(16,55,44,.06);
    }
    .ship-income-head {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-bottom:12px;
    }
    .ship-income-head h2 {
        margin:0;
        font-size:16px;
        font-weight:900;
        color:var(--ship-ink);
    }
    .ship-income-head p {
        margin:2px 0 0;
        font-size:10px;
        color:var(--ship-muted);
    }
    .ship-income-head > i {
        width:38px;
        height:38px;
        border-radius:14px;
        display:grid;
        place-items:center;
        color:var(--ship-green-dark);
        background:#e8f8f1;
    }
    .ship-income-summary {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:8px;
    }
    .ship-income-card {
        display:flex;
        flex-direction:column;
        gap:3px;
        padding:10px;
        border:1px solid #e4efeb;
        border-radius:16px;
        background:#f8fcfb;
        color:var(--ship-ink);
        text-decoration:none;
    }
    .ship-income-card.is-active {
        border-color:#a9e7d0;
        background:#eafaf3;
    }
    .ship-income-card span,
    .ship-income-card em,
    .ship-income-row em {
        font-size:10px;
        color:var(--ship-muted);
        font-style:normal;
    }
    .ship-income-card strong {
        font-size:16px;
        color:var(--ship-green-dark);
        line-height:1.1;
    }
    .ship-income-detail {
        margin-top:12px;
        border-top:1px solid #edf3f1;
        padding-top:10px;
    }
    .ship-income-tabs {
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        gap:6px;
        margin-bottom:8px;
    }
    .ship-income-tabs a {
        height:32px;
        display:grid;
        place-items:center;
        border-radius:999px;
        background:#f2f6f5;
        color:var(--ship-muted);
        font-size:11px;
        font-weight:850;
        text-decoration:none;
    }
    .ship-income-tabs a.is-active {
        background:var(--ship-green);
        color:#fff;
    }
    .ship-income-rows {
        display:grid;
        gap:6px;
    }
    .ship-income-row {
        display:grid;
        grid-template-columns:minmax(0, 1fr) auto auto;
        align-items:center;
        gap:8px;
        min-height:34px;
        padding:7px 9px;
        border-radius:12px;
        background:#f8fbfa;
        font-size:11px;
    }
    .ship-income-row span {
        min-width:0;
        font-weight:750;
        color:var(--ship-ink);
    }
    .ship-income-row b {
        color:var(--ship-green-dark);
        white-space:nowrap;
    }
    .ship-income-empty {
        padding:12px;
        border-radius:14px;
        background:#f8fbfa;
        color:var(--ship-muted);
        text-align:center;
        font-size:11px;
        font-weight:750;
    }
</style>
@endpush
