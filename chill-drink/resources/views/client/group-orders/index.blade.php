@extends('layouts.client')
@section('title', 'Đơn nhóm của tôi')
@section('content')
@include('client.group-orders._styles')
<section class="group-page" data-vue-group-orders-index>
    <div class="container group-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
            <div>
                <div class="group-eyebrow mb-2">Đặt chung tiện hơn</div>
                <h1 class="group-title mb-2">Đơn nhóm của tôi</h1>
                <p class="text-secondary mb-0">Tạo phòng, mời bạn bè và gom mọi món vào một lần thanh toán.</p>
            </div>
            <a href="{{ route('group-orders.create') }}" class="btn btn-primary group-btn"><i class="bi bi-plus-lg me-2"></i>Tạo đơn nhóm</a>
        </div>

        <div class="row g-4">
            @forelse($groups as $group)
                @php
                    $isOpen = $group->isOpen();
                    $statusLabel = match($group->status) {
                        'ordered' => 'Đã tạo đơn', 'cancelled' => 'Đã hủy', 'closed' => 'Chờ thanh toán',
                        default => $isOpen ? 'Đang mở' : 'Hết hạn',
                    };
                @endphp
                <div class="col-md-6 col-xl-4">
                    <article class="group-card h-100 p-4 d-flex flex-column" data-group-card>
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div><div class="group-eyebrow mb-1">Mã {{ $group->code }}</div><h2 class="h5 fw-bold mb-0">{{ $group->name }}</h2></div>
                            <span class="group-status {{ $isOpen ? 'is-open' : 'is-closed' }}" data-group-status><span class="group-status-dot"></span><span data-group-status-text>{{ $statusLabel }}</span></span>
                        </div>
                        @if($isOpen)
                            <div class="group-countdown mb-4" data-group-countdown data-closes-at="{{ $group->closes_at->toIso8601String() }}"><i class="bi bi-stopwatch"></i><span>Còn</span><span class="group-countdown-time" data-countdown-time>30:00</span></div>
                        @else
                            <p class="text-secondary small mb-4"><i class="bi bi-clock me-1"></i>Đóng lúc {{ $group->closes_at->format('H:i · d/m/Y') }}</p>
                        @endif
                        <div class="row g-2 mb-4">
                            <div class="col-6"><div class="group-stat"><strong>{{ $group->members_count }}</strong>Thành viên</div></div>
                            <div class="col-6"><div class="group-stat"><strong>{{ $group->items_count }}</strong>Món đã chọn</div></div>
                        </div>
                        <a href="{{ route('group-orders.show', $group->code) }}" class="btn btn-outline-primary group-btn w-100 mt-auto">Xem phòng <i class="bi bi-arrow-right ms-2"></i></a>
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="group-card empty-group"><div class="empty-group-icon"><i class="bi bi-people"></i></div><h2 class="h4 fw-bold">Chưa có đơn nhóm</h2><p class="text-secondary mb-4">Tạo phòng đầu tiên và gửi link cho mọi người cùng chọn món.</p><a href="{{ route('group-orders.create') }}" class="btn btn-primary group-btn">Tạo ngay</a></div></div>
            @endforelse
        </div>
    </div>
</section>
@if(false)
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-group-countdown]').forEach(function (countdown) {
        const closesAt = new Date(countdown.dataset.closesAt).getTime();
        const time = countdown.querySelector('[data-countdown-time]');
        const card = countdown.closest('[data-group-card]');
        let finished = false;

        const tick = function () {
            const seconds = Math.max(0, Math.ceil((closesAt - Date.now()) / 1000));
            time.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
            countdown.classList.toggle('is-urgent', seconds > 0 && seconds <= 60);

            if (seconds === 0 && !finished) {
                finished = true;
                countdown.classList.remove('is-urgent');
                countdown.classList.add('is-finished');
                countdown.querySelector('span:not([data-countdown-time])').textContent = 'Đã hết giờ';
                card?.classList.add('has-just-expired');
                const status = card?.querySelector('[data-group-status]');
                status?.classList.remove('is-open');
                status?.classList.add('is-closed');
                const statusText = card?.querySelector('[data-group-status-text]');
                if (statusText) statusText.textContent = 'Đã đóng';
                window.setTimeout(() => window.location.reload(), 1500);
            }
        };

        tick();
        window.setInterval(tick, 1000);
    });
});
</script>
@endif
@endsection
