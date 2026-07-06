@extends('layouts.client')
@section('title', $group->name)
@section('content')
@include('client.group-orders._styles')
@php
    $groupTotal = $group->items->sum(fn($item) => $item->subtotal());
    $isOpen = $group->isOpen();
    $shareUrl = route('group-orders.show', $group->code);
    $memberCount = $group->members->count();
    $isFull = $memberCount >= \App\Models\GroupOrder::MAX_MEMBERS;
@endphp
<section class="group-page">
    <div class="container group-shell">
        @if(session('success'))<div class="alert alert-success rounded-4 border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger rounded-4 border-0 shadow-sm"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>@endif

        <div class="group-card group-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 position-relative" style="z-index:1">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="group-code">{{ $group->code }}</span><span class="group-status {{ $isOpen ? 'is-open' : 'is-closed' }}"><span class="group-status-dot"></span>{{ $isOpen ? 'Đang nhận món' : 'Đã đóng' }}</span></div>
                    <h1 class="display-6 fw-bold mb-2">{{ $group->name }}</h1>
                    <p class="text-white-50 mb-1"><i class="bi bi-person me-1"></i>Chủ nhóm: {{ $group->owner->name }}</p>
                    <p class="text-white-50 mb-0"><i class="bi bi-clock me-1"></i>Chốt lúc {{ $group->closes_at->format('H:i · d/m/Y') }}</p>
                    @if($group->note)<p class="mt-3 mb-0"><i class="bi bi-chat-left-text me-2"></i>{{ $group->note }}</p>@endif
                </div>
                <div class="group-share">
                    <label class="small fw-bold mb-2 d-block">Gửi link này cho bạn bè</label>
                    <div class="group-share-row"><input id="groupShareUrl" class="form-control group-input" readonly value="{{ $shareUrl }}"><button type="button" class="btn btn-light group-btn text-nowrap" data-copy-group-link><i class="bi bi-copy me-1"></i>Sao chép</button></div>
                    <small class="d-block text-white-50 mt-2">Người tham gia cần đăng nhập để chọn món.</small>
                </div>
            </div>
        </div>

        @if(!$currentMember && $isOpen && !$isFull)
            <form method="POST" action="{{ route('group-orders.join', $group->code) }}" class="group-card p-4 mb-4">@csrf
                <div class="row g-3 align-items-end"><div class="col-md"><div class="group-eyebrow mb-1">Bước đầu tiên</div><h2 class="group-section-title mb-2">Bạn muốn hiển thị tên gì?</h2><input name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control group-input" required maxlength="100" placeholder="Tên của bạn"></div><div class="col-md-auto"><button class="btn btn-primary group-btn w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Tham gia phòng</button></div></div>
            </form>
        @elseif(!$currentMember && $isOpen && $isFull)
            <div class="group-card p-4 mb-4 text-center"><span class="group-status is-closed mb-2"><i class="bi bi-people-fill"></i>Đã đủ thành viên</span><h2 class="group-section-title mb-1">Phòng đã đạt giới hạn {{ \App\Models\GroupOrder::MAX_MEMBERS }} người</h2><p class="text-secondary mb-0">Bạn không thể tham gia thêm vào đơn nhóm này.</p></div>
        @endif

        @if($currentMember && $isOpen)
            <form method="POST" action="{{ route('group-orders.items.store', $group->code) }}" class="group-card p-4 p-md-5 mb-5">@csrf
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><div class="group-eyebrow mb-1">Món của bạn</div><h2 class="group-section-title mb-0">Chọn đồ uống cho {{ $currentMember->name }}</h2></div><span class="text-secondary small"><i class="bi bi-info-circle me-1"></i>Có thể thêm nhiều món</span></div>
                <div class="row g-3">
                    <div class="col-lg-5"><label class="group-form-label">Đồ uống</label><select name="product_id" class="form-select group-input" required><option value="">Chọn đồ uống...</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} — {{ number_format($product->price, 0, ',', '.') }}đ</option>@endforeach</select></div>
                    <div class="col-6 col-lg-2"><label class="group-form-label">Size</label><select name="size" class="form-select group-input"><option>S</option><option selected>M</option><option>L</option></select></div>
                    <div class="col-6 col-lg-2"><label class="group-form-label">Mức đường</label><select name="sugar_level" class="form-select group-input">@foreach([0,30,50,70,100] as $value)<option value="{{ $value }}" @selected($value === 100)>{{ $value }}%</option>@endforeach</select></div>
                    <div class="col-6 col-lg-2"><label class="group-form-label">Mức đá</label><select name="ice_level" class="form-select group-input">@foreach([0,30,50,70,100] as $value)<option value="{{ $value }}" @selected($value === 100)>{{ $value }}%</option>@endforeach</select></div>
                    <div class="col-6 col-lg-1"><label class="group-form-label">SL</label><input type="number" name="quantity" value="1" min="1" max="20" class="form-control group-input text-center"></div>
                    @if($toppings->isNotEmpty())<div class="col-12"><div class="group-option-box"><label class="group-form-label d-block mb-3">Topping thêm</label><div class="d-flex flex-wrap gap-2">@foreach($toppings as $topping)<label class="group-topping"><input class="form-check-input m-0" type="checkbox" name="toppings[]" value="{{ $topping->id }}"><span><strong>{{ $topping->name }}</strong> <small class="text-secondary">+{{ number_format($topping->price, 0, ',', '.') }}đ</small></span></label>@endforeach</div></div></div>@endif
                    <div class="col-md"><label class="group-form-label">Ghi chú riêng</label><input name="note" maxlength="500" class="form-control group-input" placeholder="Ví dụ: Ít ngọt, không ống hút..."></div>
                    <div class="col-md-auto d-flex align-items-end"><button class="btn btn-primary group-btn w-100"><i class="bi bi-plus-circle me-2"></i>Thêm vào đơn nhóm</button></div>
                </div>
            </form>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3"><div><div class="group-eyebrow mb-1">Đơn hiện tại</div><h2 class="group-section-title mb-0">Mọi người đã chọn gì?</h2></div><span class="text-secondary">{{ $memberCount }}/{{ \App\Models\GroupOrder::MAX_MEMBERS }} thành viên · {{ $group->items->sum('quantity') }} món</span></div>
        <div class="row g-4 mb-4">
            @forelse($group->members as $member)
                <div class="col-lg-6"><article class="group-card member-card"><header class="member-head"><div class="d-flex align-items-center gap-2"><span class="member-avatar">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span><div><h3 class="h6 fw-bold mb-0">{{ $member->name }}</h3><small class="text-secondary">{{ $member->items->sum('quantity') }} món</small></div></div><strong class="text-primary">{{ number_format($member->items->sum(fn($item) => $item->subtotal()), 0, ',', '.') }}đ</strong></header>
                    @forelse($member->items as $item)
                        <div class="member-item"><x-product-image :src="$item->product->image_url" :sku="$item->product->sku" :name="$item->product->name" :category="$item->product->category?->name" class="member-item-image" :width="160"/><div class="flex-grow-1 min-w-0"><div class="fw-bold">{{ $item->quantity }}× {{ $item->product->name }}</div><small class="text-secondary">Size {{ $item->size }} · Đường {{ $item->sugar_level }}% · Đá {{ $item->ice_level }}%@if(!empty($item->toppings)) · {{ collect($item->toppings)->pluck('name')->implode(', ') }}@endif</small>@if($item->note)<small class="d-block text-primary mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $item->note }}</small>@endif</div><div class="text-end"><strong>{{ number_format($item->subtotal(), 0, ',', '.') }}đ</strong>@if($currentMember?->id === $member->id && $isOpen)<form method="POST" action="{{ route('group-orders.items.destroy', [$group->code, $item]) }}" class="mt-1">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0" aria-label="Xóa món"><i class="bi bi-trash3"></i></button></form>@endif</div></div>
                    @empty<div class="p-4 text-center text-secondary"><i class="bi bi-cup-straw d-block fs-3 mb-2"></i>Chưa chọn món</div>@endforelse
                </article></div>
            @empty
                <div class="col-12"><div class="group-card empty-group"><div class="empty-group-icon"><i class="bi bi-cup-straw"></i></div><h3 class="h5 fw-bold">Chưa có món nào</h3><p class="text-secondary mb-0">Hãy tham gia phòng và trở thành người chọn món đầu tiên.</p></div></div>
            @endforelse
        </div>

        <div class="group-summary">
            <div><small class="text-secondary d-block">Tổng tiền cả nhóm</small><strong class="h3 text-primary mb-0">{{ number_format($groupTotal, 0, ',', '.') }}đ</strong></div>
            <div class="d-flex flex-wrap gap-2">
                @if(auth()->id() === $group->owner_id && $group->status === 'open')
                    <form method="POST" action="{{ route('group-orders.cancel', $group->code) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn nhóm này?')">@csrf<button class="btn btn-outline-danger group-btn"><i class="bi bi-x-circle me-2"></i>Hủy nhóm</button></form>
                    <form method="POST" action="{{ route('group-orders.close', $group->code) }}">@csrf<button class="btn btn-primary group-btn"><i class="bi bi-bag-check me-2"></i>Chốt đơn & thanh toán</button></form>
                @elseif(auth()->id() === $group->owner_id && $group->status === 'closed' && !$group->order_id)
                    <form method="POST" action="{{ route('group-orders.cancel', $group->code) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn nhóm này?')">@csrf<button class="btn btn-outline-danger group-btn"><i class="bi bi-x-circle me-2"></i>Hủy nhóm</button></form>
                    <form method="POST" action="{{ route('group-orders.resume', $group->code) }}">@csrf<button class="btn btn-primary group-btn"><i class="bi bi-credit-card me-2"></i>Tiếp tục thanh toán</button></form>
                @elseif($group->status === 'ordered')
                    <span class="group-status is-open"><i class="bi bi-check-circle"></i>Đã tạo đơn #{{ $group->order_id }}</span>
                @elseif($group->status === 'cancelled')
                    <span class="group-status is-closed"><i class="bi bi-x-circle"></i>Đơn đã hủy</span>
                @elseif(!$isOpen)
                    <span class="group-status is-closed"><i class="bi bi-lock"></i>Đơn đã đóng</span>
                @endif
            </div>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('[data-copy-group-link]');
    const input = document.getElementById('groupShareUrl');
    button?.addEventListener('click', async function () {
        try { await navigator.clipboard.writeText(input.value); } catch (error) { input.select(); document.execCommand('copy'); }
        button.innerHTML = '<i class="bi bi-check2 me-1"></i>Đã sao chép';
        window.setTimeout(() => button.innerHTML = '<i class="bi bi-copy me-1"></i>Sao chép', 1800);
    });
});
</script>
@endsection
