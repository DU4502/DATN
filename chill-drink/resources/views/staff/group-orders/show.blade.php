@extends('layouts.staff')

@section('page-title', 'Chi tiết đơn nhóm')
@section('hide-topbar-search', true)

@section('content')
@php
    $total = $groupOrder->items->sum(fn ($item) => $item->subtotal());
    $statusMap = ['open' => ['Đang mở', 'success'], 'closed' => ['Chờ thanh toán', 'warning'], 'ordered' => ['Đã đặt hàng', 'info'], 'cancelled' => ['Đã hủy', 'danger']];
    [$statusLabel, $statusColor] = $statusMap[$groupOrder->status] ?? [$groupOrder->status, 'secondary'];
    $changedBy = $groupOrder->status_changed_by ? \App\Models\User::find($groupOrder->status_changed_by) : null;
    $allowedNext = ['open' => ['closed' => 'Đóng nhóm', 'cancelled' => 'Hủy đơn nhóm'], 'closed' => ['ordered' => 'Xác nhận đã đặt hàng', 'cancelled' => 'Hủy đơn nhóm']];
@endphp
<style>
    .admin-group-product { display:flex;align-items:center;gap:.85rem;min-width:280px; }
    .admin-group-product-image { width:58px;height:58px;flex:0 0 58px;border:1px solid #e1ebe8;border-radius:13px;object-fit:cover;background:#f1f7f5; }
</style>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a href="{{ route('staff.group-orders.index') }}" class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left me-1"></i>Danh sách đơn nhóm
        </a>
        <h2 class="h4 fw-bold mt-2 mb-0">{{ $groupOrder->name }}</h2>
    </div>
    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis fs-6">{{ $statusLabel }}</span>
</div>

<!-- Thống kê -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="admin-card p-3 h-100">
            <small class="text-secondary">Mã phòng</small>
            <strong class="d-block text-primary fs-5" style="letter-spacing:.05em;">{{ $groupOrder->code }}</strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card p-3 h-100">
            <small class="text-secondary">Chủ nhóm</small>
            <strong class="d-block">{{ $groupOrder->owner->name ?? '—' }}</strong>
            <small class="text-secondary">{{ $groupOrder->owner->email ?? '' }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card p-3 h-100">
            <small class="text-secondary">Thành viên</small>
            <strong class="d-block fs-5">{{ $groupOrder->members->count() }} / {{ \App\Models\GroupOrder::MAX_MEMBERS }}</strong>
            <small class="text-secondary">{{ $groupOrder->items->sum('quantity') }} món đã chọn</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card p-3 h-100">
            <small class="text-secondary">Tổng tạm tính</small>
            <strong class="d-block text-primary fs-5">{{ number_format($total, 0, ',', '.') }}đ</strong>
        </div>
    </div>
</div>

<!-- Thông tin đổi trạng thái -->
@if($groupOrder->status_changed_at)
<div class="alert alert-info border-0 mb-4" style="border-radius:12px;">
    <i class="bi bi-clock-history me-2"></i>
    Trạng thái được cập nhật lần cuối lúc
    <strong>{{ $groupOrder->status_changed_at->format('H:i · d/m/Y') }}</strong>
    @if($changedBy)
        bởi <strong>{{ $changedBy->name }}</strong>
        @if($changedBy->isStaffOnly())<span class="badge bg-warning text-dark ms-1">Nhân viên</span>@endif
        @if($changedBy->isAdmin())<span class="badge bg-info ms-1">Admin</span>@endif
    @endif
</div>
@endif

@if($groupOrder->note)
<div class="alert alert-light border mb-4">
    <i class="bi bi-chat-left-text me-2"></i>{{ $groupOrder->note }}
</div>
@endif

<!-- Nút đổi trạng thái -->
@if(isset($allowedNext[$groupOrder->status]) && count($allowedNext[$groupOrder->status]) > 0)
<div class="admin-card p-4 mb-4">
    <h5 class="fw-bold mb-3">Cập nhật trạng thái đơn nhóm</h5>
    <div class="d-flex flex-wrap gap-3">
        @foreach($allowedNext[$groupOrder->status] as $newStatus => $btnLabel)
        <form action="{{ route('staff.group-orders.updateStatus', $groupOrder) }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="status" value="{{ $newStatus }}">
            @if($newStatus === 'cancelled')
                <button type="submit" class="btn btn-outline-danger"
                        onclick="return confirm('Bạn có chắc muốn hủy đơn nhóm này?')">
                    <i class="bi bi-x-circle me-2"></i>{{ $btnLabel }}
                </button>
            @elseif($newStatus === 'ordered')
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i>{{ $btnLabel }}
                </button>
            @else
                <button type="submit" class="btn btn-primary" style="background:#00a870;border-color:#00a870;">
                    <i class="bi bi-arrow-right-circle me-2"></i>{{ $btnLabel }}
                </button>
            @endif
        </form>
        @endforeach
    </div>
</div>
@endif

<!-- Danh sách thành viên & món -->
<section class="admin-card">
    <div class="p-4 border-bottom">
        <h3 class="h5 fw-bold mb-1">Món của từng thành viên</h3>
        <small class="text-secondary">
            Thời hạn đóng: {{ $groupOrder->closes_at->format('H:i · d/m/Y') }}
        </small>
    </div>
    @forelse($groupOrder->members as $member)
    <div class="p-4 border-bottom">
        <div class="d-flex justify-content-between gap-3 mb-3">
            <div>
                <strong>{{ $member->name }}</strong>
                <small class="text-secondary d-block">{{ $member->items->sum('quantity') }} món</small>
            </div>
            <strong class="text-primary">{{ number_format($member->items->sum(fn ($item) => $item->subtotal()), 0, ',', '.') }}đ</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                @forelse($member->items as $item)
                <tr>
                    <td>
                        <div class="admin-group-product">
                            <x-product-image :src="$item->product->image_url" :sku="$item->product->sku ?? null"
                                :name="$item->product->name" :category="$item->product->category?->name"
                                class="admin-group-product-image" :width="140"/>
                            <div>
                                <strong>{{ $item->quantity }}× {{ $item->product->name }}</strong>
                                <small class="text-secondary d-block">
                                    Size {{ $item->size }} · Đường {{ $item->sugar_level }}% · Đá {{ $item->ice_level }}%
                                    @if($item->toppings) · {{ collect($item->toppings)->pluck('name')->implode(', ') }}@endif
                                </small>
                                @if($item->note)
                                    <small class="text-primary d-block mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $item->note }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-end fw-bold">{{ number_format($item->subtotal(), 0, ',', '.') }}đ</td>
                </tr>
                @empty
                <tr><td class="text-secondary">Chưa chọn món</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center text-secondary py-5">Chưa có thành viên tham gia.</div>
    @endforelse
</section>
@endsection
