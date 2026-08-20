@extends(auth()->user()->preferredAdminLayout())

@section('title', 'Đối soát COD')
@section('page-title', 'Đối soát COD')
@section('hide-topbar-search', true)

@section('content')
@php
    $indexRoute = $rootMode
        ? route('admin.super-admin.manage.cod-settlements.index')
        : route('admin.cod-settlements.index');
@endphp

<style>
    .cod-stat,.cod-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 8px 28px rgba(15,23,42,.045)}
    .cod-money{font-variant-numeric:tabular-nums;color:#b45309}
    .cod-order-list{background:#f8fafc;border-radius:12px;padding:.75rem 1rem}
    .cod-order-row{display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem}
    .cod-order-row:last-child{border-bottom:0}
</style>

<section class="d-grid gap-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="small text-secondary mb-1">Tiền mặt tài xế đang giữ hộ công ty</div>
            <h2 class="h4 fw-bold mb-1">Đối soát COD shipper</h2>
            <div class="small text-secondary">
                @if($rootMode)
                    Super Admin xem công nợ COD toàn hệ thống. Mỗi shipper chỉ nộp tiền về home branch cố định của mình.
                @else
                    Chỉ hiển thị shipper có home branch là chi nhánh của bạn. Chỉ xác nhận sau khi đã nhận tiền mặt thực tế.
                @endif
            </div>
        </div>
        <a href="{{ $indexRoute }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4"><div class="cod-stat p-4"><div class="small text-secondary">Tổng tiền COD chưa nộp</div><div class="fs-3 fw-bold cod-money mt-1">{{ number_format($totalPending,0,',','.') }}đ</div></div></div>
        <div class="col-lg-4"><div class="cod-stat p-4"><div class="small text-secondary">Shipper đang có công nợ</div><div class="fs-3 fw-bold mt-1">{{ $pendingRows->count() }}</div></div></div>
        <div class="col-lg-4"><div class="cod-stat p-4"><div class="small text-secondary">Đơn COD chưa đối soát</div><div class="fs-3 fw-bold mt-1">{{ $totalPendingOrders }}</div></div></div>
    </div>

    <div class="d-grid gap-3">
        @forelse($pendingRows as $row)
            @php
                $shipper = $row['shipper'];
                $confirmRoute = $rootMode
                    ? route('admin.super-admin.manage.cod-settlements.confirm', $shipper)
                    : route('admin.cod-settlements.confirm', $shipper);
                $locationLabel = 'Home branch: '.($shipper->user?->branch?->name ?? 'Chưa gán');
            @endphp
            <article class="cod-card p-4">
                <div class="row g-4 align-items-start">
                    <div class="col-xl-3">
                        <div class="small text-secondary fw-semibold mb-2">SHIPPER</div>
                        <div class="fw-bold">{{ $shipper->user?->name ?? 'Shipper #'.$shipper->id }}</div>
                        <div class="small text-secondary">{{ $shipper->code }} · {{ $shipper->phone ?: $shipper->user?->phone }}</div>
                        <div class="small mt-2"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $locationLabel }}</div>
                    </div>
                    <div class="col-xl-2">
                        <div class="small text-secondary fw-semibold">PHẢI NỘP</div>
                        <div class="fs-4 fw-bold cod-money mt-1">{{ number_format($row['pending_amount'],0,',','.') }}đ</div>
                        <div class="small text-secondary">{{ $row['pending_order_count'] }} đơn COD</div>
                    </div>
                    <div class="col-xl-4">
                        <details>
                            <summary class="small fw-semibold" style="cursor:pointer">Xem các đơn đang giữ tiền</summary>
                            <div class="cod-order-list mt-2">
                                @foreach($row['items'] as $item)
                                    <div class="cod-order-row">
                                        <span><strong>{{ $item->order_code ?: '#'.$item->order_id }}</strong><br><span class="text-secondary">{{ $item->collected_at?->format('d/m H:i') }} · {{ $item->order?->branch?->name ?? 'Chi nhánh cũ' }}</span></span>
                                        <strong>{{ number_format((int)$item->amount,0,',','.') }}đ</strong>
                                    </div>
                                @endforeach
                                @if($row['pending_order_count'] > $row['items']->count())
                                    <div class="small text-secondary pt-2">Và {{ $row['pending_order_count'] - $row['items']->count() }} đơn khác.</div>
                                @endif
                            </div>
                        </details>
                    </div>
                    <div class="col-xl-3">
                        <form method="POST" action="{{ $confirmRoute }}" onsubmit="return confirm('Bạn đã thực nhận đủ {{ number_format($row['pending_amount'],0,',','.') }}đ tiền mặt từ shipper này?');">
                            @csrf
                            <div class="alert alert-light border py-2 px-3 small mb-2">
                                <i class="bi bi-building me-1"></i> Nộp tại <strong>{{ $row['home_branch_name'] ?? 'Home branch chưa xác định' }}</strong>
                            </div>
                            <input type="text" name="note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Ghi chú (không bắt buộc)">
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-cash-coin me-1"></i>Xác nhận đã nhận tiền</button>
                            <div class="small text-secondary mt-2">Thao tác sẽ chốt toàn bộ {{ $row['pending_order_count'] }} đơn COD chưa đối soát của shipper tại home branch.</div>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="cod-card p-5 text-center">
                <i class="bi bi-cash-coin text-success" style="font-size:2.4rem"></i>
                <div class="fw-bold mt-3">Không có tiền COD đang chờ nộp</div>
                <div class="small text-secondary mt-1">Khi shipper giao một đơn COD và thu tiền khách, công nợ sẽ tự xuất hiện ở đây.</div>
            </div>
        @endforelse
    </div>

    <div class="cod-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><div class="fw-bold">Lịch sử nộp COD</div><div class="small text-secondary">50 lần đối soát gần nhất trong phạm vi bạn đang quản lý.</div></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Thời gian</th><th>Shipper</th><th>Chi nhánh nhận</th><th>Số đơn</th><th>Số tiền</th><th>Người xác nhận</th><th>Ghi chú</th></tr></thead>
                <tbody>
                    @forelse($history as $settlement)
                        <tr>
                            <td>{{ $settlement->confirmed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td><strong>{{ $settlement->shipper?->user?->name ?? 'Shipper #'.$settlement->shipper_id }}</strong><br><span class="small text-secondary">{{ $settlement->shipper?->code }}</span></td>
                            <td>{{ $settlement->branch?->name ?? '-' }}</td>
                            <td>{{ $settlement->order_count }}</td>
                            <td class="fw-bold text-success">{{ number_format((int)$settlement->amount,0,',','.') }}đ</td>
                            <td>{{ $settlement->confirmer?->name ?? 'Hệ thống' }}</td>
                            <td class="small text-secondary">{{ $settlement->note ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">Chưa có lịch sử đối soát COD.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
