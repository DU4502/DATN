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
<section class="group-page" data-vue-group-order-room data-state-url="{{ route('group-orders.state', $group->code) }}" data-presence-url="{{ $isOpen ? route('group-orders.presence', $group->code) : '' }}" data-leave-url="{{ $isOpen && auth()->id() === $group->owner_id ? route('group-orders.leave', $group->code) : '' }}">
    <div class="container group-shell">
        @if(session('success'))<div class="alert alert-success rounded-4 border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger rounded-4 border-0 shadow-sm"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger rounded-4 border-0 shadow-sm"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>@endif

        <div class="group-card group-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 position-relative" style="z-index:1">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="group-code">{{ $group->code }}</span><span class="group-status {{ $isOpen ? 'is-open' : 'is-closed' }}"><span class="group-status-dot"></span>{{ $isOpen ? 'Đang nhận món' : 'Đã đóng' }}</span></div>
                    <h1 class="display-6 fw-bold mb-2">{{ $group->name }}</h1>
                    <p class="text-white-50 mb-1"><i class="bi bi-person me-1"></i>Chủ nhóm: {{ $group->owner->name }}</p>
                    @if($group->branch)<p class="text-white-50 mb-1"><i class="bi bi-shop me-1"></i>Chi nhánh: <strong class="text-white">{{ $group->branch->name }}</strong></p>@endif
                    <p class="text-white-50 mb-0"><i class="bi bi-clock me-1"></i>@if($isOpen)Còn <strong class="text-white" data-group-countdown data-closes-at="{{ $group->closes_at->toIso8601String() }}">30:00</strong> để chọn món · Kết thúc lúc <strong class="text-white">{{ $group->closes_at->format('H:i · d/m/Y') }}</strong> @else Đã đóng lúc {{ $group->closes_at->format('H:i · d/m/Y') }} @endif</p>
                    @if($group->note)<p class="mt-3 mb-0"><i class="bi bi-chat-left-text me-2"></i>{{ $group->note }}</p>@endif
                </div>
                <div class="group-share">
                    <label class="small fw-bold mb-2 d-block">Gửi link này cho bạn bè</label>
                    <div class="group-share-row"><input id="groupShareUrl" class="form-control group-input" readonly value="{{ $shareUrl }}"><button type="button" class="btn btn-light group-btn text-nowrap" data-copy-group-link><i class="bi bi-copy me-1"></i>Sao chép</button></div>
                    <small class="d-block text-white-50 mt-2">Người tham gia cần đăng nhập để chọn món.</small>
                </div>
            </div>
        </div>

        @if($isOpen && auth()->id() !== $group->owner_id)
        @endif

        <div data-group-participation>
        @if(!$currentMember && $isOpen && !$isFull)
            <form method="POST" action="{{ route('group-orders.join', $group->code) }}" class="group-card p-4 mb-4" data-group-async-action data-group-join>@csrf
                <div class="row g-3 align-items-end"><div class="col-md"><div class="group-eyebrow mb-1">Bước đầu tiên</div><h2 class="group-section-title mb-2">Bạn muốn hiển thị tên gì?</h2><input name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control group-input" required maxlength="100" placeholder="Tên của bạn"></div><div class="col-md-auto"><button class="btn btn-primary group-btn w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Tham gia phòng</button></div></div>
            </form>
        @elseif(!$currentMember && $isOpen && $isFull)
            <div class="group-card p-4 mb-4 text-center"><span class="group-status is-closed mb-2"><i class="bi bi-people-fill"></i>Đã đủ thành viên</span><h2 class="group-section-title mb-1">Phòng đã đạt giới hạn {{ \App\Models\GroupOrder::MAX_MEMBERS }} người</h2><p class="text-secondary mb-0">Bạn không thể tham gia thêm vào đơn nhóm này.</p></div>
        @endif

        @if($currentMember && $isOpen)
            <form method="POST" action="{{ route('group-orders.items.store', $group->code) }}" class="group-card group-order-form mb-5" data-group-async-action>@csrf
                <div class="group-order-form-head"><div><div class="group-eyebrow mb-1">Món của bạn</div><h2 class="group-section-title mb-0">Chọn đồ uống cho {{ $currentMember->name }}</h2></div><span class="group-status is-open"><i class="bi bi-plus-circle"></i>Thêm nhiều món</span></div>
                <div class="group-order-form-body">
                <div class="row g-3 mb-3">
                    @php($selectedProduct = $products->firstWhere('id', (int) old('product_id')))
                    <div class="col-lg-6"><div class="group-field-panel"><label class="group-form-label" for="groupProductSearch">Đồ uống</label><div class="group-product-picker" data-product-picker><div class="group-product-search"><i class="bi bi-search"></i><input id="groupProductSearch" type="search" class="form-control group-input" value="{{ $selectedProduct?->name }}" placeholder="Tìm và chọn đồ uống..." autocomplete="off" required data-product-search><i class="bi bi-chevron-down"></i></div><input type="hidden" name="product_id" value="{{ old('product_id') }}" data-product-value><div class="group-product-menu" data-product-menu>
                        @foreach($products as $product)
                            <button type="button" class="group-product-option" data-product-option data-value="{{ $product->id }}" data-name="{{ $product->name }}" data-search="{{ $product->name }} {{ $product->sku }}" data-toppings="{{ $productToppingMap->get($product->id, collect())->implode(',') }}">
                                <x-product-image :src="$product->image_url" :sku="$product->sku" :name="$product->name" :category="$product->category?->name" class="group-product-option-image" />
                                <span class="group-product-option-copy"><strong>{{ $product->name }}</strong><span class="text-secondary small">{{ $product->sku }} · {{ $product->category?->name }}</span></span>
                                <small class="group-product-option-price">{{ number_format($product->price, 0, ',', '.') }}đ</small>
                            </button>
                        @endforeach
                        <div class="group-search-empty" data-product-empty><i class="bi bi-exclamation-circle me-1"></i>Không tìm thấy đồ uống phù hợp.</div>
                    </div></div></div></div>
                    <div class="col-lg-6"><div class="group-field-panel"><div class="group-custom-grid"><div><label class="group-form-label">Kích cỡ</label><select name="size" class="form-select group-input"><option selected>S</option><option>M</option><option>L</option></select></div><div><label class="group-form-label">Mức đường</label><select name="sugar_level" class="form-select group-input">@foreach([0,30,50,70,100] as $value)<option value="{{ $value }}" @selected($value === 100)>{{ $value }}%</option>@endforeach</select></div><div><label class="group-form-label">Mức đá</label><select name="ice_level" class="form-select group-input">@foreach([0,30,50,70,100] as $value)<option value="{{ $value }}" @selected($value === 100)>{{ $value }}%</option>@endforeach</select></div><div><label class="group-form-label">Số lượng</label><input type="number" name="quantity" value="1" min="1" max="20" class="form-control group-input text-center"></div></div></div></div>
                    @if($toppings->isNotEmpty())
                        @php($initialToppingIds = $selectedProduct ? $productToppingMap->get($selectedProduct->id, collect()) : collect())
                        <div class="col-12" data-group-topping-section>
                            <div class="group-option-box">
                                <label class="group-form-label d-block mb-1">Món thêm phù hợp</label>
                                <p class="text-secondary small mb-3" data-topping-help>{{ $selectedProduct ? 'Chỉ hiển thị topping dùng được với món đã chọn.' : 'Hãy chọn đồ uống trước để xem topping phù hợp.' }}</p>
                                <div class="d-flex flex-wrap gap-2" data-group-toppings>
                                    @foreach($toppings as $topping)
                                        <label class="group-topping {{ $initialToppingIds->contains((int) $topping->id) ? '' : 'd-none' }}" data-topping-id="{{ $topping->id }}"><input class="form-check-input m-0" type="checkbox" name="toppings[]" value="{{ $topping->id }}"><span><strong>{{ $topping->name }}</strong> <small class="text-secondary">+{{ number_format($topping->price, 0, ',', '.') }}đ</small></span></label>
                                    @endforeach
                                </div>
                                <div class="text-secondary small {{ $selectedProduct ? 'd-none' : '' }}" data-choose-product-for-toppings><i class="bi bi-cup-straw me-1"></i>Chưa chọn đồ uống.</div>
                                <div class="text-secondary small d-none" data-no-toppings><i class="bi bi-info-circle me-1"></i>Món này không có món thêm.</div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="group-submit-row"><div class="group-note"><label class="group-form-label">Ghi chú riêng</label><input name="note" maxlength="500" class="form-control group-input" placeholder="Ví dụ: Ít ngọt, không ống hút..."></div><button class="btn btn-primary group-btn group-add-button"><i class="bi bi-plus-circle me-2"></i>Thêm vào đơn nhóm</button></div>
                </div>
            </form>
        @endif
        @if($currentMember)
            <div class="mb-4 group-chat-hide-launcher" data-vue-group-chat
                 data-group-id="{{ $group->id }}"
                 data-group-is-open="{{ $group->isOpen() ? '1' : '0' }}"
                 data-messages-url="{{ route('group-orders.messages', $group->code) }}"
                 data-send-url="{{ route('group-orders.messages.send', $group->code) }}"
                 data-read-url="{{ route('group-orders.messages.read', $group->code) }}"
                 data-current-member-id="{{ $currentMember->id }}"
                 data-members='@json($group->members->map(fn($member) => ["id" => $member->id, "name" => $member->name])->values())'></div>
        @endif
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3" data-group-order-heading><div><div class="group-eyebrow mb-1">Đơn hiện tại</div><h2 class="group-section-title mb-0">Mọi người đã chọn gì?</h2></div><span class="text-secondary">{{ $memberCount }}/{{ \App\Models\GroupOrder::MAX_MEMBERS }} thành viên · {{ $group->items->sum('quantity') }} món</span></div>
        <div class="row g-4 mb-4" data-group-members>
            @forelse($group->members as $member)
                <div class="col-lg-6"><article class="group-card member-card"><header class="member-head"><div class="d-flex align-items-center gap-2"><span class="member-avatar">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span><div><h3 class="h6 fw-bold mb-0">{{ $member->name }}</h3><small class="text-secondary">{{ $member->items->sum('quantity') }} món</small></div></div><strong class="text-primary">{{ number_format($member->items->sum(fn($item) => $item->subtotal()), 0, ',', '.') }}đ</strong></header>
                    @forelse($member->items as $item)
                        <div class="member-item"><x-product-image :src="$item->product->image_url" :sku="$item->product->sku" :name="$item->product->name" :category="$item->product->category?->name" class="member-item-image" :width="160"/><div class="flex-grow-1 min-w-0"><div class="fw-bold">{{ $item->quantity }}× {{ $item->product->name }}</div><small class="text-secondary">Kích cỡ {{ $item->size }} · Đường {{ $item->sugar_level }}% · Đá {{ $item->ice_level }}%@if(!empty($item->toppings)) · {{ collect($item->toppings)->pluck('name')->implode(', ') }}@endif</small>@if($item->note)<small class="d-block text-primary mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $item->note }}</small>@endif</div><div class="text-end"><strong>{{ number_format($item->subtotal(), 0, ',', '.') }}đ</strong>@if($currentMember?->id === $member->id && $isOpen)<div class="group-item-actions"><div class="group-quantity-stepper"><form method="POST" action="{{ route('group-orders.items.decrement', [$group->code, $item]) }}" data-group-async-action>@csrf @method('PATCH')<button class="group-stepper-button" aria-label="Giảm một phần {{ $item->product->name }}" title="Giảm 1 phần"><i class="bi bi-dash-lg"></i></button></form><span class="group-stepper-value" aria-label="Số lượng">{{ $item->quantity }}</span><form method="POST" action="{{ route('group-orders.items.increment', [$group->code, $item]) }}" data-group-async-action>@csrf @method('PATCH')<button class="group-stepper-button is-add" aria-label="Thêm một phần {{ $item->product->name }}" title="Thêm 1 phần"><i class="bi bi-plus-lg"></i></button></form></div><form method="POST" action="{{ route('group-orders.items.destroy', [$group->code, $item]) }}" data-group-async-action>@csrf @method('DELETE')<button class="group-item-action is-remove" aria-label="Xóa món" title="Xóa món"><i class="bi bi-trash3"></i></button></form></div>@endif</div></div>
                    @empty<div class="p-4 text-center text-secondary"><i class="bi bi-cup-straw d-block fs-3 mb-2"></i>Chưa chọn món</div>@endforelse
                </article></div>
            @empty
                <div class="col-12"><div class="group-card empty-group"><div class="empty-group-icon"><i class="bi bi-cup-straw"></i></div><h3 class="h5 fw-bold">Chưa có món nào</h3><p class="text-secondary mb-0">Hãy tham gia phòng và trở thành người chọn món đầu tiên.</p></div></div>
            @endforelse
        </div>

        <div class="group-summary" data-group-summary>
            <div><small class="text-secondary d-block">Tổng tiền cả nhóm</small><strong class="h3 text-primary mb-0">{{ number_format($groupTotal, 0, ',', '.') }}đ</strong></div>
            <div class="d-flex flex-wrap gap-2">
                @if(auth()->id() === $group->owner_id && $group->status === 'open')
                    <form method="POST" action="{{ route('group-orders.cancel', $group->code) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn nhóm này?')">@csrf<button class="btn btn-outline-danger group-btn"><i class="bi bi-x-circle me-2"></i>Hủy nhóm</button></form>
                    <form method="POST" action="{{ route('group-orders.close', $group->code) }}">@csrf<button class="btn btn-primary group-btn"><i class="bi bi-bag-check me-2"></i>Chốt đơn & thanh toán</button></form>
                @elseif($currentMember && $group->status === 'open')
                    <form method="POST" action="{{ route('group-orders.leave-room', $group->code) }}" onsubmit="return confirm('Rời phòng? Món đã chọn và các tin nhắn riêng liên quan của bạn sẽ bị xóa.');">@csrf<button class="btn btn-outline-danger group-btn"><i class="bi bi-box-arrow-right me-2"></i>Rời phòng</button></form>
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
@if(false)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const showLiveMessage = function (message, isError = false) {
        document.querySelector('.group-live-toast')?.remove();
        const toast = document.createElement('div');
        toast.className = 'group-live-toast' + (isError ? ' is-error' : '');
        toast.textContent = message;
        document.body.appendChild(toast);
        window.setTimeout(() => toast.remove(), 2600);
    };

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('form[data-group-async-action]');
        if (!form || event.defaultPrevented) return;
        event.preventDefault();

        const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
        if (submitter) submitter.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const html = await response.text();
            const page = new DOMParser().parseFromString(html, 'text/html');
            const error = page.querySelector('.alert-danger');
            if (!response.ok || error) {
                showLiveMessage(error?.textContent.trim() || 'Không thể cập nhật món. Vui lòng thử lại.', true);
                return;
            }

            ['[data-group-order-heading]', '[data-group-members]', '[data-group-summary]'].forEach(selector => {
                const current = document.querySelector(selector);
                const updated = page.querySelector(selector);
                if (current && updated) current.replaceWith(updated);
            });
            showLiveMessage(page.querySelector('.alert-success')?.textContent.trim() || 'Đã cập nhật đơn nhóm.');
        } catch (error) {
            showLiveMessage('Kết nối bị gián đoạn. Vui lòng thử lại.', true);
        } finally {
            if (submitter?.isConnected) submitter.disabled = false;
        }
    });

    const button = document.querySelector('[data-copy-group-link]');
    const input = document.getElementById('groupShareUrl');
    button?.addEventListener('click', async function () {
        try { await navigator.clipboard.writeText(input.value); } catch (error) { input.select(); document.execCommand('copy'); }
        button.innerHTML = '<i class="bi bi-check2 me-1"></i>Đã sao chép';
        window.setTimeout(() => button.innerHTML = '<i class="bi bi-copy me-1"></i>Sao chép', 1800);
    });

    const productPicker = document.querySelector('[data-product-picker]');
    const productSearch = document.querySelector('[data-product-search]');
    const productValue = document.querySelector('[data-product-value]');
    const productEmpty = document.querySelector('[data-product-empty]');
    if (productPicker && productSearch && productValue) {
        const options = Array.from(productPicker.querySelectorAll('[data-product-option]'));
        const toppingSection = document.querySelector('[data-group-topping-section]');
        const toppingLabels = Array.from(document.querySelectorAll('[data-topping-id]'));
        const noToppings = document.querySelector('[data-no-toppings]');
        const chooseProductForToppings = document.querySelector('[data-choose-product-for-toppings]');
        const toppingHelp = document.querySelector('[data-topping-help]');
        const normalize = value => value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');

        const showToppingsFor = option => {
            const allowedIds = new Set((option?.dataset.toppings || '').split(',').filter(Boolean).map(Number));
            chooseProductForToppings?.classList.toggle('d-none', Boolean(option));
            if (toppingHelp) toppingHelp.textContent = option ? 'Chỉ hiển thị topping dùng được với món đã chọn.' : 'Hãy chọn đồ uống trước để xem topping phù hợp.';
            toppingLabels.forEach(label => {
                const isAllowed = allowedIds.has(Number(label.dataset.toppingId));
                label.classList.toggle('d-none', !isAllowed);
                if (!isAllowed) label.querySelector('input').checked = false;
            });
            noToppings?.classList.toggle('d-none', !option || allowedIds.size > 0);
        };

        const openPicker = () => productPicker.classList.add('is-open');
        productSearch.addEventListener('focus', openPicker);
        productSearch.addEventListener('click', openPicker);
        productSearch.addEventListener('input', function () {
            const keyword = normalize(productSearch.value.trim());
            productValue.value = '';
            showToppingsFor(null);
            productSearch.setCustomValidity('Vui lòng chọn một đồ uống trong danh sách.');
            const matches = options.filter(option => normalize(option.dataset.search).includes(keyword));
            options.forEach(option => option.hidden = !matches.includes(option));
            productEmpty?.classList.toggle('is-visible', matches.length === 0);
            openPicker();
        });

        options.forEach(option => option.addEventListener('click', function () {
            productValue.value = option.dataset.value;
            productSearch.value = option.dataset.name;
            productSearch.setCustomValidity('');
            productSearch.classList.remove('is-invalid');
            productPicker.classList.remove('is-open');
            showToppingsFor(option);
        }));

        document.addEventListener('click', event => {
            if (!productPicker.contains(event.target)) productPicker.classList.remove('is-open');
        });
        productSearch.addEventListener('keydown', event => {
            if (event.key === 'Escape') productPicker.classList.remove('is-open');
        });
        productSearch.closest('form')?.addEventListener('submit', event => {
            if (!productValue.value) {
                event.preventDefault();
                productSearch.classList.add('is-invalid');
                productSearch.setCustomValidity('Vui lòng tìm và chọn một đồ uống trong danh sách.');
                productSearch.reportValidity();
                openPicker();
            }
        });
    }

    const countdown = document.querySelector('[data-group-countdown]');
    if (countdown) {
        const closesAt = new Date(countdown.dataset.closesAt).getTime();
        const tick = function () {
            const seconds = Math.max(0, Math.ceil((closesAt - Date.now()) / 1000));
            const minutes = Math.floor(seconds / 60);
            countdown.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
            if (seconds === 0) window.location.reload();
        };
        tick();
        window.setInterval(tick, 1000);
    }

    const presenceUrl = @json($isOpen && auth()->id() === $group->owner_id ? route('group-orders.presence', $group->code) : null);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (presenceUrl && csrfToken) {
        let presenceTimer = null;
        let hasReloadedForClosing = false;
        const syncPresence = async function () {
            try {
                const response = await fetch(presenceUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) return;
                const state = await response.json();
                if (!state.is_open) {
                    if (presenceTimer) window.clearInterval(presenceTimer);
                    if (!hasReloadedForClosing) {
                        hasReloadedForClosing = true;
                        window.location.reload();
                    }
                }
            } catch (error) {
                // Giữ trạng thái gần nhất khi kết nối tạm thời gián đoạn.
            }
        };
        syncPresence();
        presenceTimer = window.setInterval(syncPresence, 10000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) syncPresence(); });
    }

});
</script>
@endif
@endsection
