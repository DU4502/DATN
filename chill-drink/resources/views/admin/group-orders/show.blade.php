@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

@section('page-title', 'Chi tiết đơn nhóm')

@section('content')
@php
    $total = $groupOrder->items->sum(fn ($item) => $item->subtotal());
    $statusLabel = match($groupOrder->status) { 'open' => 'Đang mở', 'closed' => 'Chờ thanh toán', 'ordered' => 'Đã đặt hàng', 'cancelled' => 'Đã hủy', default => $groupOrder->status };
@endphp
<style>
    .admin-group-product { display: flex; align-items: center; gap: .85rem; min-width: 280px; }
    .admin-group-product-image { width: 58px; height: 58px; flex: 0 0 58px; border: 1px solid #e1ebe8; border-radius: 13px; object-fit: cover; background: #f1f7f5; }
    .admin-group-chat { max-height: 440px; overflow-y: auto; background: #f7fbfa; }
    .admin-group-chat-message { max-width: 760px; margin: 0 auto; padding: .8rem 1rem; border-bottom: 1px solid #e5efed; background: #fff; }
    .admin-group-chat-message:last-child { border-bottom: 0; }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><a href="{{ route('admin.group-orders.index') }}" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left me-1"></i>Danh sách đơn nhóm</a><h2 class="h4 fw-bold mt-2 mb-0">{{ $groupOrder->name }}</h2></div>
    <span class="badge bg-primary-subtle text-primary-emphasis fs-6">{{ $statusLabel }}</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="admin-card p-3 h-100"><small class="text-secondary">Mã phòng</small><strong class="d-block text-primary fs-5">{{ $groupOrder->code }}</strong></div></div>
    <div class="col-md-3"><div class="admin-card p-3 h-100"><small class="text-secondary">Chủ nhóm</small><strong class="d-block">{{ $groupOrder->owner->name ?? '—' }}</strong><small>{{ $groupOrder->owner->email ?? '' }}</small></div></div>
    <div class="col-md-3"><div class="admin-card p-3 h-100"><small class="text-secondary">Thành viên</small><strong class="d-block fs-5">{{ $groupOrder->members->count() }} / {{ \App\Models\GroupOrder::MAX_MEMBERS }} thành viên</strong><small class="text-secondary">{{ $groupOrder->items->sum('quantity') }} món đã chọn</small></div></div>
    <div class="col-md-3"><div class="admin-card p-3 h-100"><small class="text-secondary">Tổng tạm tính</small><strong class="d-block text-primary fs-5">{{ number_format($total, 0, ',', '.') }}đ</strong></div></div>
</div>

@if($groupOrder->note)<div class="alert alert-info border-0 rounded-3"><i class="bi bi-chat-left-text me-2"></i>{{ $groupOrder->note }}</div>@endif

<section class="admin-card">
    <div class="p-4 border-bottom"><h3 class="h5 fw-bold mb-1">Món của từng thành viên</h3><small class="text-secondary">Chốt lúc {{ $groupOrder->closes_at->format('H:i · d/m/Y') }}</small></div>
    @forelse($groupOrder->members as $member)
        <div class="p-4 border-bottom">
            <div class="d-flex justify-content-between gap-3 mb-3"><div><strong>{{ $member->name }}</strong><small class="text-secondary d-block">{{ $member->items->sum('quantity') }} món</small></div><strong class="text-primary">{{ number_format($member->items->sum(fn ($item) => $item->subtotal()), 0, ',', '.') }}đ</strong></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>
            @forelse($member->items as $item)
                <tr><td><div class="admin-group-product"><x-product-image :src="$item->product->image_url" :sku="$item->product->sku" :name="$item->product->name" :category="$item->product->category?->name" class="admin-group-product-image" :width="140"/><div><strong>{{ $item->quantity }}× {{ $item->product->name }}</strong><small class="text-secondary d-block">Kích cỡ {{ $item->size }} · Đường {{ $item->sugar_level }}% · Đá {{ $item->ice_level }}%@if($item->toppings) · {{ collect($item->toppings)->pluck('name')->implode(', ') }}@endif</small>@if($item->note)<small class="text-primary d-block mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $item->note }}</small>@endif</div></div></td><td class="text-end fw-bold">{{ number_format($item->subtotal(), 0, ',', '.') }}đ</td></tr>
            @empty<tr><td class="text-secondary">Chưa chọn món</td></tr>@endforelse
            </tbody></table></div>
        </div>
    @empty
        <div class="text-center text-secondary py-5">Chưa có thành viên tham gia.</div>
    @endforelse
</section>

<section class="admin-card mt-4">
    <div class="p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="h5 fw-bold mb-1"><i class="bi bi-chat-square-text me-2 text-primary"></i>Lịch sử trò chuyện</h3>
            <small class="text-secondary">Chế độ giám sát: Super Admin chỉ xem, không thể gửi tin nhắn.</small>
        </div>
        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $groupOrder->messages->count() }} tin nhắn</span>
    </div>
    <div class="admin-group-chat">
        @forelse($groupOrder->messages->sortBy('id') as $message)
            <article class="admin-group-chat-message">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <strong>{{ $message->sender?->name ?? 'Thành viên đã rời phòng' }}</strong>
                    <small class="text-secondary">{{ $message->created_at->format('H:i · d/m/Y') }}</small>
                </div>
                <div class="small mb-2"><span class="badge {{ $message->recipient ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success-emphasis' }}">{{ $message->recipient ? 'Tin nhắn riêng tới '.$message->recipient->name : 'Chat chung' }}</span></div>
                <p class="mb-0 text-dark">{{ $message->content }}</p>
            </article>
        @empty
            <div class="text-center text-secondary py-5"><i class="bi bi-chat-dots d-block fs-3 mb-2"></i>Chưa có tin nhắn trong phòng.</div>
        @endforelse
    </div>
</section>
@endsection
