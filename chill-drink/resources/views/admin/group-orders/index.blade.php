@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Đơn nhóm')

@section('content')
<style>
    .admin-page { min-height: calc(100vh - 65px); }
    .group-admin-stat { padding: 1.1rem 1.25rem; border: 1px solid var(--admin-border); border-radius: 16px; background: #fff; }
    .group-admin-stat strong { display: block; color: var(--admin-primary); font-size: 1.55rem; }
    .group-admin-code { color: #087560; font-weight: 800; letter-spacing: .08em; }
</style>

<div class="row g-3 mb-4">
    @foreach([['all', 'Tất cả', 'bi-collection'], ['open', 'Đang mở', 'bi-broadcast'], ['closed', 'Chờ thanh toán', 'bi-lock'], ['ordered', 'Đã đặt hàng', 'bi-check-circle']] as [$key, $label, $icon])
        <div class="col-6 col-xl-3"><div class="group-admin-stat"><span class="text-secondary small"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span><strong>{{ $stats[$key] }}</strong></div></div>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.group-orders.index') }}" class="admin-card p-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-6"><label class="admin-kicker mb-2 d-block">Tìm kiếm</label><input class="admin-input" name="q" value="{{ $filters['q'] }}" placeholder="Mã phòng, tên nhóm, chủ nhóm..."></div>
        <div class="col-md-3"><label class="admin-kicker mb-2 d-block">Trạng thái</label><select class="admin-filter" name="status"><option value="">Tất cả</option><option value="open" @selected($filters['status'] === 'open')>Đang mở</option><option value="closed" @selected($filters['status'] === 'closed')>Chờ thanh toán</option><option value="ordered" @selected($filters['status'] === 'ordered')>Đã đặt hàng</option><option value="cancelled" @selected($filters['status'] === 'cancelled')>Đã hủy</option></select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Lọc</button><a class="btn btn-outline-primary" href="{{ route('admin.group-orders.index') }}">Đặt lại</a></div>
    </div>
</form>

<section class="admin-card group-admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Mã phòng</th><th>Nhóm / Chủ nhóm</th><th>Thời gian</th><th class="text-center">Thành viên</th><th class="text-center">Món</th><th>Trạng thái</th><th class="text-end">Chi tiết</th></tr></thead>
            <tbody>
            @forelse($groups as $group)
                @php
                    $status = match($group->status) { 'open' => ['Đang mở', 'success'], 'closed' => ['Chờ thanh toán', 'warning'], 'ordered' => ['Đã đặt hàng', 'primary'], 'cancelled' => ['Đã hủy', 'secondary'], default => [$group->status, 'secondary'] };
                @endphp
                <tr>
                    <td><span class="group-admin-code">{{ $group->code }}</span></td>
                    <td><strong class="d-block">{{ $group->name }}</strong><small class="text-secondary">{{ $group->owner->name ?? '—' }} · {{ $group->owner->email ?? '' }}</small></td>
                    <td><small class="d-block text-secondary">Tạo {{ $group->created_at?->format('H:i d/m/Y') }}</small><small>Chốt {{ $group->closes_at->format('H:i d/m/Y') }}</small></td>
                    <td class="text-center fw-bold">{{ $group->members_count }}</td>
                    <td class="text-center fw-bold">{{ $group->items_count }}</td>
                    <td>
                        <span class="badge bg-{{ $status[1] }}-subtle text-{{ $status[1] }}-emphasis d-inline-block mb-1">{{ $status[0] }}</span>
                        @if($group->status_changed_at)
                            @php
                                $groupUpdater = $group->status_changed_by ? \App\Models\User::find($group->status_changed_by) : null;
                            @endphp
                            <small class="text-muted d-block" style="font-size:0.72rem;">
                                <i class="bi bi-clock-history me-1"></i>{{ $group->status_changed_at->format('H:i · d/m/Y') }}
                                @if($groupUpdater)
                                    ({{ $groupUpdater->name }})
                                @endif
                            </small>
                        @endif
                    </td>
                    <td class="text-end"><a class="admin-action text-decoration-none" href="{{ route('admin.group-orders.show', $group) }}" title="Xem chi tiết"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-5"><strong class="d-block text-dark mb-1">Chưa có đơn nhóm</strong>Đơn nhóm mới sẽ xuất hiện tại đây.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-top">{{ $groups->links('pagination::bootstrap-5') }}</div>
</section>
@endsection
