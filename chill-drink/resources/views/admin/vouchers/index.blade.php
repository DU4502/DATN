@extends('layouts.admin')

@section('page-title', 'Voucher')
@section('hide-topbar-search', true)

@section('content')
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Quản lý khuyến mãi</p>
        <h2 class="h2 fw-bold mb-1">Voucher</h2>
        <p class="text-secondary mb-0">Quản lý mã giảm giá, mã theo rank và mã đổi điểm.</p>
    </div>
    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary align-self-start align-self-lg-auto">
        <i class="bi bi-plus-lg me-1"></i>Thêm mã
    </a>
</section>

<section class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Tổng mã</p>
            <p class="admin-value text-primary mb-0">{{ $stats['total'] }}</p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Đang hoạt động</p>
            <p class="admin-value mb-0">{{ $stats['active'] }}</p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Đã lên lịch</p>
            <p class="admin-value mb-0">{{ $stats['scheduled'] }}</p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Lượt đã dùng</p>
            <p class="admin-value mb-0" style="color: var(--a-danger);">{{ $stats['used'] }}</p>
        </div>
    </div>
</section>

<section class="admin-card p-4 mb-4">
    <form method="GET" action="{{ route('admin.vouchers.index') }}" class="row g-3 align-items-end">
        <div class="col-lg-6">
            <label for="q" class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" class="admin-input" placeholder="Tìm mã hoặc mô tả voucher">
        </div>
        <div class="col-sm-7 col-lg-3">
            <label for="status" class="admin-kicker mb-2 d-block">Trạng thái</label>
            <select id="status" name="status" class="admin-filter">
                <option value="">Tất cả trạng thái</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-5 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Lọc</button>
            <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-primary" title="Xóa lọc"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</section>

<section class="admin-card admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle" style="min-width: 1120px;">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Mô tả</th>
                    <th>Giá trị</th>
                    <th>Giảm tối đa</th>
                    <th>Rank</th>
                    <th>Điểm đổi</th>
                    <th>Sử dụng</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $voucher)
                    @php
                        $isScheduled = $voucher->status && $voucher->starts_at && $voucher->starts_at->gt(now());
                        $isExpired = $voucher->expires_at && $voucher->expires_at->lt(now());
                        $isOutOfUses = ! $voucher->hasRemainingUses();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">{{ $voucher->code }}</div>
                            <small class="text-secondary">{{ $voucher->type === 'percent' ? 'Phần trăm' : 'Cố định' }}</small>
                        </td>
                        <td style="max-width: 260px;">
                            <span class="d-block text-truncate">{{ $voucher->description ?: '-' }}</span>
                            @if($voucher->min_order > 0)
                                <small class="text-secondary">Đơn từ {{ number_format($voucher->min_order, 0, ',', '.') }}đ</small>
                            @endif
                        </td>
                        <td><span class="badge badge-soft-muted">{{ $voucher->formattedValue() }}</span></td>
                        <td class="fw-bold">
                            {{ $voucher->max_discount ? number_format($voucher->max_discount, 0, ',', '.') . 'đ' : '-' }}
                        </td>
                        <td><span class="badge badge-soft-muted">{{ $voucher->rankLabel() }}</span></td>
                        <td class="fw-bold">
                            {{ $voucher->point_cost > 0 ? number_format($voucher->point_cost, 0, ',', '.') . ' điểm' : '-' }}
                        </td>
                        <td>{{ $voucher->usageText() }}</td>
                        <td>
                            @if(! $voucher->status)
                                <span class="badge badge-soft-danger">Đã tắt</span>
                            @elseif($isScheduled)
                                <span class="badge badge-soft-muted">Đã lên lịch</span>
                            @elseif($isExpired)
                                <span class="badge badge-soft-danger">Hết hạn</span>
                            @elseif($isOutOfUses)
                                <span class="badge badge-soft-danger">Hết lượt</span>
                            @else
                                <span class="badge badge-soft-primary">Hoạt động</span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ optional($voucher->created_at)->format('d/m/Y H:i') ?: '-' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="admin-action text-decoration-none" title="Sửa"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa voucher {{ $voucher->code }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-action" title="Xóa" style="color: var(--a-danger);"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Chưa có voucher phù hợp</div>
                            <div>Hãy tạo mã mới hoặc xóa bộ lọc hiện tại.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination-footer">
        <p class="text-secondary mb-0">Đang hiển thị {{ $vouchers->count() }} / {{ $vouchers->total() }} voucher</p>
        {{ $vouchers->onEachSide(1)->links() }}
    </div>
</section>
@endsection
