@extends('layouts.client')

@section('title', 'Theo dõi đơn hàng ' . $order->displayCode())

@section('content')
@php
    $status = \App\Support\OrderStatus::normalize((string) $order->status);
    $journey = $journey ?? [
        'state' => 'pending_confirmation',
        'label' => \App\Support\OrderStatus::label($status),
        'stage' => \App\Support\OrderStatus::label($status),
        'message' => 'Đơn hàng đang được xử lý.',
    ];
    $steps = [
        ['key' => 'pending_confirmation', 'label' => 'Đã đặt hàng', 'icon' => 'bi-receipt'],
        ['key' => 'finding_shipper', 'label' => 'Xác nhận & điều phối', 'icon' => 'bi-diagram-3'],
        ['key' => 'shipper_assigned', 'label' => 'Tài xế đã nhận', 'icon' => 'bi-person-check'],
        ['key' => 'shipper_picked_up', 'label' => 'Đã lấy hàng', 'icon' => 'bi-bag-check'],
        ['key' => 'delivering', 'label' => 'Đang giao', 'icon' => 'bi-scooter'],
        ['key' => 'delivered', 'label' => 'Đã giao', 'icon' => 'bi-house-check'],
    ];
    $stepOrder = collect($steps)->pluck('key')->values()->all();
    $currentIndex = array_search($journey['state'], $stepOrder, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $isCodPending = $order->payment_method === 'cod' && $order->payment_status !== 'paid';
    $shipperAccepted = (bool) ($shipperAccepted ?? false);
    $shipperInfo = is_array($shipperInfo ?? null) ? $shipperInfo : null;
    $shipperPhone = (string) ($shipperInfo['phone'] ?? '');
    $shipperTel = preg_replace('/[^0-9+]/', '', $shipperPhone);
@endphp

<style>
    .tracking-page{background:#f5f8f7;min-height:calc(100vh - 80px);padding:28px 0 60px}
    .tracking-shell{max-width:1180px;margin:0 auto}
    .tracking-top{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:18px}
    .tracking-back{display:inline-flex;align-items:center;gap:7px;color:#64748b;text-decoration:none;font-weight:700;margin-bottom:10px}
    .tracking-title{font-size:1.6rem;font-weight:900;margin:0;color:#17211f}
    .tracking-code{color:#0d9373;font-weight:800;margin-top:4px}
    .tracking-status{padding:8px 14px;border-radius:999px;background:#e7f7f1;color:#0b7e64;font-size:.82rem;font-weight:800;white-space:nowrap}
    .tracking-hero{display:grid;grid-template-columns:minmax(0,1.3fr) auto;gap:18px;align-items:center;background:linear-gradient(135deg,#ffffff 0%,#f1fbf8 100%);border:1px solid #e3ece9;border-radius:22px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 30px rgba(20,74,63,.05)}
    .tracking-hero-label{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;font-weight:900;text-transform:uppercase;color:#0d9373;margin-bottom:10px}
    .tracking-hero-dot{width:10px;height:10px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 6px rgba(22,163,74,.12);animation:trackingPulse 1.6s infinite}
    .tracking-hero-title{font-size:1.2rem;font-weight:900;color:#162420;margin-bottom:6px}
    .tracking-hero-text{color:#687774;line-height:1.6}
    .tracking-hero-mini{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:18px;background:#ffffff;border:1px solid #e3ece9;box-shadow:0 12px 22px rgba(13,147,115,.08)}
    .tracking-hero-mini i{font-size:1.15rem;color:#0d9373}
    .tracking-progress{background:#fff;border:1px solid #e3ece9;border-radius:20px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 30px rgba(20,74,63,.05)}
    .tracking-steps{display:grid;grid-template-columns:repeat(6,1fr);gap:0;position:relative}
    .tracking-step{position:relative;text-align:center;color:#94a3b8;z-index:1}
    .tracking-step:not(:last-child)::after{content:"";position:absolute;left:56%;right:-44%;top:19px;height:3px;background:#e5ebe9;z-index:-1}
    .tracking-step.is-done:not(:last-child)::after{background:#13a47e}
    .tracking-step-icon{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;margin:0 auto 8px;background:#eef2f1;color:#94a3b8;border:3px solid #fff;box-shadow:0 0 0 1px #e2e8e6}
    .tracking-step.is-done,.tracking-step.is-current{color:#0d7f65;font-weight:800}
    .tracking-step.is-done .tracking-step-icon,.tracking-step.is-current .tracking-step-icon{background:#0d9373;color:#fff;box-shadow:0 0 0 4px rgba(13,147,115,.12)}
    .tracking-step.is-current .tracking-step-icon{animation:trackingPulse 1.7s infinite}
    .tracking-step-label{font-size:.82rem;font-weight:750;line-height:1.35}
    .tracking-grid{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(300px,.75fr);gap:18px;align-items:start}
    .tracking-main,.tracking-side-card{background:#fff;border:1px solid #e3ece9;border-radius:20px;box-shadow:0 10px 30px rgba(20,74,63,.05)}
    .tracking-main{padding:6px 18px 18px;overflow:hidden}
    .tracking-main .delivery-live-card{border:0!important;box-shadow:none!important;margin-top:0!important}
    .tracking-main .delivery-live-map{height:470px!important;border-radius:16px}
    .tracking-side{display:grid;gap:14px}
    .tracking-side-card{padding:18px}
    .tracking-side-title{font-weight:900;margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .tracking-info-row{display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;color:#52615e;font-size:.92rem}
    .tracking-info-row i{color:#0d9373;font-size:1rem;margin-top:1px}
    .tracking-pay{border-radius:15px;padding:14px;background:#f7fbfa;border:1px solid #e1eeea}
    .tracking-pay.cod{background:#fff8e8;border-color:#f3dfad}
    .tracking-pay-label{font-size:.76rem;text-transform:uppercase;color:#74837f;font-weight:800}
    .tracking-pay-amount{font-size:1.45rem;font-weight:900;color:#0d9373;margin-top:2px}
    .tracking-pay.cod .tracking-pay-amount{color:#a06400}
    .tracking-help{font-size:.82rem;color:#75827f;line-height:1.5}
    .tracking-driver{background:linear-gradient(145deg,#ffffff,#f2fbf8);border-color:#d6ebe4}
    .tracking-driver-profile{display:flex;align-items:center;gap:11px;margin-bottom:13px}
    .tracking-driver-avatar{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:#0d9373;color:#fff;font-size:1.15rem;font-weight:900;box-shadow:0 8px 18px rgba(13,147,115,.2)}
    .tracking-driver-name{font-weight:900;color:#18322b}.tracking-driver-meta{font-size:.8rem;color:#71807c;margin-top:2px}
    .tracking-driver-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px}.tracking-driver-actions .btn{border-radius:12px;font-weight:800}
    @keyframes trackingPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.06);opacity:.72}}
    @media(max-width:900px){.tracking-hero,.tracking-grid{grid-template-columns:1fr}.tracking-main .delivery-live-map{height:390px!important}.tracking-steps{grid-template-columns:repeat(3,1fr);row-gap:18px}.tracking-step:nth-child(3n):after{display:none}.tracking-step:not(:last-child)::after{left:60%;right:-40%}}
    @media(max-width:600px){.tracking-page{padding:16px 0 40px}.tracking-top{padding:0 2px}.tracking-title{font-size:1.25rem}.tracking-status{font-size:.72rem}.tracking-hero{padding:16px}.tracking-hero-title{font-size:1.02rem}.tracking-progress{padding:16px 10px}.tracking-step-label{font-size:.67rem}.tracking-step-icon{width:34px;height:34px}.tracking-step:not(:last-child)::after{top:16px}.tracking-main{padding:4px 8px 12px}.tracking-main .delivery-live-map{height:330px!important}.tracking-side-card{padding:15px}}
</style>

<main class="tracking-page">
    <div class="container tracking-shell">
        <div class="tracking-top">
            <div>
                <a href="{{ route('orders.index', ['order' => $order->id]) }}" class="tracking-back"><i class="bi bi-arrow-left"></i>Đơn hàng của tôi</a>
                <h1 class="tracking-title">Theo dõi đơn hàng</h1>
                <div class="tracking-code">{{ $order->displayCode() }}</div>
            </div>
            <div class="tracking-status" data-track-status>{{ $journey['label'] }}</div>
        </div>

        <section class="tracking-hero">
            <div>
                <div class="tracking-hero-label"><span class="tracking-hero-dot"></span> Trạng thái hiện tại</div>
                <div class="tracking-hero-title" data-track-stage>{{ $journey['stage'] }}</div>
                <div class="tracking-hero-text" data-track-message>{{ $journey['message'] }}</div>
            </div>
            <div class="tracking-hero-mini">
                <i class="bi bi-clock-history"></i>
                <div>
                    <div class="small text-secondary">Cập nhật gần nhất</div>
                    <strong>{{ optional($order->updated_at)->format('H:i · d/m/Y') }}</strong>
                </div>
            </div>
        </section>

        <section class="tracking-progress" aria-label="Tiến trình đơn hàng">
            <div class="tracking-steps" data-track-steps>
                @foreach($steps as $index => $step)
                    @php
                        $done = $currentIndex >= 0 && $index < $currentIndex;
                        $current = $index === $currentIndex;
                    @endphp
                    <div class="tracking-step {{ $done ? 'is-done' : '' }} {{ $current ? 'is-current' : '' }}" data-step-key="{{ $step['key'] }}">
                        <div class="tracking-step-icon"><i class="bi {{ $done ? 'bi-check-lg' : $step['icon'] }}"></i></div>
                        <div class="tracking-step-label">{{ $step['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="tracking-grid">
            <section class="tracking-main">
                <x-delivery-live-tracking :order="$order" :live-url="$liveUrl" />
            </section>

            <aside class="tracking-side">
                <section class="tracking-side-card tracking-driver {{ $shipperAccepted ? '' : 'd-none' }}" data-driver-card>
                    <div class="tracking-side-title"><i class="bi bi-scooter text-primary"></i>Tài xế giao hàng</div>
                    <div class="tracking-driver-profile">
                        <div class="tracking-driver-avatar" data-driver-avatar>{{ mb_strtoupper(mb_substr((string) ($shipperInfo['name'] ?? 'T'), 0, 1)) }}</div>
                        <div class="min-w-0">
                            <div class="tracking-driver-name text-truncate" data-driver-name>{{ $shipperInfo['name'] ?? 'Tài xế Chill Drink' }}</div>
                            <div class="tracking-driver-meta" data-driver-vehicle>{{ collect([$shipperInfo['vehicle_type'] ?? null, $shipperInfo['license_plate'] ?? null])->filter()->join(' · ') }}</div>
                        </div>
                    </div>
                    <div class="tracking-driver-actions">
                        <a href="{{ $shipperTel !== '' ? 'tel:'.$shipperTel : '#' }}"
                           class="btn btn-success {{ $shipperTel === '' ? 'disabled' : '' }}"
                           data-driver-call>
                            <i class="bi bi-telephone-fill me-1"></i>Gọi tài xế
                        </a>
                        <x-order-delivery-chat
                            :order="$order"
                            :messages-url="route('orders.delivery-chat.messages', $order)"
                            :send-url="route('orders.delivery-chat.send', $order)"
                            viewer="customer"
                            peer-label="Tài xế"
                            button-text="Chat"
                            button-class="btn btn-outline-primary w-100" />
                    </div>
                    <div class="small text-secondary mt-2">Chat chỉ phục vụ chuyến hiện tại và tự hết hạn sau 24 giờ.</div>
                </section>

                <section class="tracking-side-card">
                    <div class="tracking-side-title"><i class="bi bi-geo-alt-fill text-primary"></i>Thông tin giao hàng</div>
                    <div class="tracking-info-row"><i class="bi bi-person"></i><span>{{ $order->customerName() ?: 'Khách hàng' }}</span></div>
                    <div class="tracking-info-row"><i class="bi bi-telephone"></i><span>{{ $order->customerPhone() ?: 'Chưa cập nhật' }}</span></div>
                    <div class="tracking-info-row mb-0"><i class="bi bi-geo-alt"></i><span>{{ $order->getShippingAddress() }}</span></div>
                </section>

                <section class="tracking-side-card">
                    <div class="tracking-side-title"><i class="bi bi-wallet2 text-primary"></i>Thanh toán</div>
                    <div class="tracking-pay {{ $isCodPending ? 'cod' : '' }}">
                        <div class="tracking-pay-label">{{ $isCodPending ? 'Tài xế sẽ thu khi giao' : 'Tổng thanh toán' }}</div>
                        <div class="tracking-pay-amount">{{ number_format((int) ($order->display_total ?? $order->total ?? 0), 0, ',', '.') }}đ</div>
                        <div class="small mt-1 {{ $isCodPending ? 'text-warning-emphasis' : 'text-success' }}">
                            @if($isCodPending)
                                <i class="bi bi-cash-coin me-1"></i>Thanh toán tiền mặt (COD)
                            @else
                                <i class="bi bi-check-circle-fill me-1"></i>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Theo phương thức đã chọn' }}
                            @endif
                        </div>
                    </div>
                </section>

                <section class="tracking-side-card">
                    <div class="tracking-side-title"><i class="bi bi-info-circle text-primary"></i>Theo dõi trực tiếp</div>
                    <div class="tracking-help">Ngay khi quán xác nhận, hệ thống <strong>điều phối shipper song song với pha chế</strong>. Khi tài xế được gán chuyến, thông tin gọi/chat sẽ mở và vị trí GPS của đúng chuyến sẽ được cập nhật trên bản đồ.</div>
                </section>
            </aside>
        </div>
    </div>
</main>

<script>
(() => {
    const statusEl = document.querySelector('[data-track-status]');
    const stageEl = document.querySelector('[data-track-stage]');
    const messageEl = document.querySelector('[data-track-message]');
    const progress = document.querySelector('[data-track-steps]');
    const liveRoot = document.querySelector('[data-delivery-live]');
    const driverCard = document.querySelector('[data-driver-card]');
    const driverName = document.querySelector('[data-driver-name]');
    const driverVehicle = document.querySelector('[data-driver-vehicle]');
    const driverAvatar = document.querySelector('[data-driver-avatar]');
    const driverCall = document.querySelector('[data-driver-call]');
    if (!liveRoot || !progress) return;

    const order = ['pending_confirmation','finding_shipper','shipper_assigned','shipper_picked_up','delivering','delivered'];

    liveRoot.addEventListener('delivery:tracking-updated', (event) => {
        const data = event.detail || {};
        const state = data.timeline_state || 'pending_confirmation';
        const currentIndex = order.indexOf(state);
        if (statusEl) statusEl.textContent = data.timeline_label || data.stage || data.status_label || 'Theo dõi đơn hàng';
        if (stageEl) stageEl.textContent = data.stage || data.timeline_label || 'Theo dõi đơn hàng';
        if (messageEl && data.message) messageEl.textContent = data.message;

        if (driverCard) {
            const visible = !!data.shipper_accepted && !!data.shipper;
            driverCard.classList.toggle('d-none', !visible);
            if (visible) {
                const name = data.shipper.name || 'Tài xế Chill Drink';
                if (driverName) driverName.textContent = name;
                if (driverAvatar) driverAvatar.textContent = name.trim().charAt(0).toUpperCase() || 'T';
                if (driverVehicle) driverVehicle.textContent = [data.shipper.vehicle_type, data.shipper.license_plate].filter(Boolean).join(' · ');
                if (driverCall) {
                    const phone = String(data.shipper.phone || '').replace(/[^0-9+]/g, '');
                    driverCall.href = phone ? `tel:${phone}` : '#';
                    driverCall.classList.toggle('disabled', !phone);
                }
            }
        }

        progress.querySelectorAll('[data-step-key]').forEach((el, index) => {
            el.classList.toggle('is-done', currentIndex >= 0 && index < currentIndex);
            el.classList.toggle('is-current', index === currentIndex);
            const icon = el.querySelector('.tracking-step-icon i');
            if (icon) {
                if (index < currentIndex) {
                    icon.className = 'bi bi-check-lg';
                }
            }
        });
    });
})();
</script>
@endsection
