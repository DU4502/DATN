@extends('layouts.staff')

@section('page-title', 'Đơn nhóm')
@section('search-placeholder', 'Tìm mã phòng, tên nhóm...')
@section('topbar-search-action', route('staff.group-orders.index'))

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Đơn nhóm</h2>
        <p class="text-secondary mb-0">Chi nhánh: <strong>{{ auth()->user()->branch?->name ?? 'Chưa được gán' }}</strong></p>
    </div>
</div>

@if(($filters['scope'] ?? '') === 'active')
<div class="alert alert-info border-0 rounded-4 d-flex justify-content-between align-items-center gap-3" role="status">
    <span><i class="bi bi-funnel me-2"></i>Đang hiển thị dữ liệu: <strong>Đơn nhóm đang mở hoặc chờ xử lý</strong></span>
    <a href="{{ route('staff.group-orders.index') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
</div>
@endif

<!-- Thống kê nhanh -->
<div class="row g-3 mb-4">
    @php
        $statItems = [
            ['label' => 'Tất cả', 'value' => $stats['all'], 'status' => '', 'color' => '#64748b'],
            ['label' => 'Đang mở', 'value' => $stats['open'], 'status' => 'open', 'color' => '#00a870'],
            ['label' => 'Chờ thanh toán', 'value' => $stats['closed'], 'status' => 'closed', 'color' => '#d97706'],
            ['label' => 'Đã đặt', 'value' => $stats['ordered'], 'status' => 'ordered', 'color' => '#0284c7'],
        ];
    @endphp
    @foreach($statItems as $stat)
    <div class="col-6 col-lg-3">
        <a href="{{ route('staff.group-orders.index', array_merge(request()->except('scope'), ['status' => $stat['status']])) }}"
           class="admin-card p-3 d-flex align-items-center justify-content-between text-decoration-none"
           style="color:inherit;border-left:4px solid {{ $stat['color'] }};">
            <div>
                <div class="text-secondary" style="font-size:.78rem;font-weight:600;">{{ $stat['label'] }}</div>
                <div class="fw-bold" style="font-size:1.8rem;line-height:1;color:{{ $stat['color'] }};">{{ $stat['value'] }}</div>
            </div>
            <i class="bi bi-people-fill" style="font-size:1.5rem;color:{{ $stat['color'] }};opacity:.25;"></i>
        </a>
    </div>
    @endforeach
</div>

<!-- Bộ lọc -->
<form method="GET" action="{{ route('staff.group-orders.index') }}" class="mb-4">
    @if(($filters['scope'] ?? '') === 'active')<input type="hidden" name="scope" value="active">@endif
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input class="admin-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã phòng, tên nhóm, chủ nhóm...">
        </div>
        <div class="col-md-3">
            <label class="admin-kicker mb-2 d-block">Trạng thái</label>
            <select class="admin-filter" name="status">
                <option value="" @selected(($filters['status'] ?? '') === '')>Tất cả</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Đang mở</option>
                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Chờ thanh toán</option>
                <option value="ordered" @selected(($filters['status'] ?? '') === 'ordered')>Đã đặt hàng</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Đã hủy</option>
            </select>
        </div>
        <div class="col-md-5 d-flex gap-2 justify-content-end">
            <button class="btn btn-primary px-4" type="submit">Lọc</button>
            <a href="{{ route('staff.group-orders.index') }}" class="btn btn-outline-primary px-4">Làm mới</a>
        </div>
    </div>
</form>

<!-- Bảng -->
<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Mã phòng</th>
                    <th>Tên nhóm</th>
                    <th>Chủ nhóm</th>
                    <th class="text-center">Thành viên</th>
                    <th class="text-center">Món</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center">Người đổi TT</th>
                    <th class="text-center">Thời gian đổi</th>
                    <th>Đóng lúc</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                @php
                    $statusMap = ['open' => ['Đang mở','success'], 'closed' => ['Chờ TT','warning'], 'ordered' => ['Đã đặt','info'], 'cancelled' => ['Đã hủy','danger']];
                    [$statusLabel, $statusColor] = $statusMap[$group->status] ?? [$group->status, 'secondary'];
                    $changedBy = $group->status_changed_by ? \App\Models\User::find($group->status_changed_by) : null;
                @endphp
                <tr>
                    <td class="fw-bold text-primary" style="letter-spacing:.05em;">{{ $group->code }}</td>
                    <td class="fw-semibold">{{ $group->name }}</td>
                    <td>
                        <div class="fw-semibold">{{ $group->owner?->name ?? '—' }}</div>
                        <small class="text-secondary">{{ $group->owner?->email }}</small>
                    </td>
                    <td class="text-center">{{ $group->members_count }}</td>
                    <td class="text-center">{{ $group->items_count }}</td>
                    <td class="text-center">
                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis">{{ $statusLabel }}</span>
                        @if($group->order)
                            <div class="mt-1">
                                <a href="{{ route('staff.orders.index', ['search' => $group->order->displayCode()]) }}" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none" title="Xem đơn hàng">
                                    <i class="bi bi-receipt me-1"></i>{{ $group->order->displayCode() }}
                                </a>
                            </div>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($changedBy)
                            <span class="badge bg-light text-dark" style="font-size:.72rem;">
                                <i class="bi bi-person me-1"></i>{{ $changedBy->name }}
                            </span>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($group->status_changed_at)
                            <span class="text-secondary small">{{ $group->status_changed_at->format('d/m H:i') }}</span>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td>
                        <small class="text-secondary">{{ $group->closes_at->format('H:i · d/m/Y') }}</small>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-2 justify-content-center align-items-center">
                            <a href="{{ route('staff.group-orders.show', $group) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-secondary py-5">
                        <div class="fw-bold text-dark mb-1">Chưa có đơn nhóm</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center p-4 border-top" style="background:var(--admin-soft-2,#f8f9fa);">
        <p class="text-secondary mb-0">Hiển thị {{ $groups->count() }} đơn nhóm</p>
        {{ $groups->links('pagination::bootstrap-5') }}
    </div>
</section>
@endsection
