@extends('layouts.client')

@section('title', $result === 'success' ? 'Đặt hàng thành công' : 'Kết quả thanh toán')
@section('hide-client-chatbox', '1')

@section('content')
@php
    $isSuccess = $result === 'success';
    $isFailed = $result === 'failed';
    $paymentLabels = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'VNPay',
    ];
    $liveTrackingUrl = null;
    $deliveryChatMessagesUrl = null;
    $deliveryChatSendUrl = null;
    $guestTrackToken = null;
    if ($order) {
        if (auth()->check() && (int) auth()->id() === (int) $order->user_id) {
            $liveTrackingUrl = route('orders.delivery-tracking', $order);
            $deliveryChatMessagesUrl = route('orders.delivery-chat.messages', $order);
            $deliveryChatSendUrl = route('orders.delivery-chat.send', $order);
        } elseif ($order->isGuest()) {
            $guestTrackToken = \App\Support\GuestOrderAccess::tokenFromRequest(request(), $order);
            if (filled($guestTrackToken)) {
                $liveTrackingUrl = route('checkout.guest.live', ['order' => $order->id, 'token' => $guestTrackToken]);
                $deliveryChatMessagesUrl = route('checkout.guest.delivery-chat.messages', ['order' => $order->id, 'token' => $guestTrackToken]);
                $deliveryChatSendUrl = route('checkout.guest.delivery-chat.send', ['order' => $order->id, 'token' => $guestTrackToken]);
            }
        }
    }
@endphp

<style>
    .order-result-page {
        min-height: calc(100vh - 88px);
        padding: 64px 0 88px;
        background: linear-gradient(180deg, #effcf8 0%, #f8fbfa 52%, #ffffff 100%);
    }

    .result-shell {
        max-width: 880px;
        margin: 0 auto;
    }

    .result-main {
        padding: 48px;
        border: 1px solid #dcebe7;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 24px 60px rgba(14, 72, 61, 0.1);
        text-align: center;
    }

    .result-icon {
        width: 84px;
        height: 84px;
        margin: 0 auto 24px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        font-size: 2.5rem;
        color: #ffffff;
        background: {{ $isSuccess ? '#0d9373' : ($isFailed ? '#e59a16' : '#dc3545') }};
    }

    .order-summary {
        margin-top: 32px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        border: 1px solid #e3ece9;
        border-radius: 16px;
        overflow: hidden;
        text-align: left;
    }

    .summary-item {
        padding: 20px;
        background: #fbfefd;
    }

    .summary-item + .summary-item {
        border-left: 1px solid #e3ece9;
    }

    .summary-label {
        display: block;
        margin-bottom: 5px;
        color: #71807c;
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .result-actions {
        margin-top: 30px;
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .result-tracking-panel {
        min-width: 0;
    }

    @media (min-width: 992px) {
        .order-result-page {
            min-height: calc(100svh - 78px);
            padding: 28px 0;
            display: flex;
            align-items: center;
        }

        .result-shell.has-live-tracking {
            max-width: none;
            display: flex;
            justify-content: center;
        }

        .result-main.result-main--cinema {
            width: min(1120px, calc(100vw - 48px), calc((100svh - 132px) * 16 / 9));
            aspect-ratio: 16 / 9;
            padding: 22px;
            display: grid;
            grid-template-columns: minmax(280px, 0.42fr) minmax(0, 0.58fr);
            grid-template-rows: minmax(0, 1fr) auto;
            gap: 16px 18px;
            text-align: left;
            overflow: hidden;
        }

        .result-main--cinema .result-copy {
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .result-main--cinema .result-icon {
            width: 58px;
            height: 58px;
            margin: 0 0 14px;
            font-size: 1.85rem;
        }

        .result-main--cinema h1 {
            font-size: clamp(1.8rem, 2.35vw, 2.4rem);
            line-height: 1.08;
            margin-bottom: 10px !important;
        }

        .result-main--cinema .result-message {
            font-size: 1rem !important;
            line-height: 1.45;
        }

        .result-main--cinema .order-summary {
            margin-top: 18px;
            grid-template-columns: 1fr;
            border-radius: 14px;
        }

        .result-main--cinema .summary-item {
            padding: 12px 14px;
        }

        .result-main--cinema .summary-item + .summary-item {
            border-left: 0;
            border-top: 1px solid #e3ece9;
        }

        .result-main--cinema .summary-label {
            font-size: 0.7rem;
        }

        .result-main--cinema .result-tracking-panel {
            grid-column: 2;
            grid-row: 1 / span 2;
            min-height: 0;
            display: flex;
        }

        .result-main--cinema .result-tracking-panel .delivery-live-card {
            width: 100%;
            height: 100%;
            margin: 0 !important;
            display: flex;
            flex-direction: column;
            border-radius: 18px;
        }

        .result-main--cinema .result-tracking-panel .delivery-live-head,
        .result-main--cinema .result-tracking-panel .delivery-live-foot {
            padding: 10px 12px;
        }

        .result-main--cinema .result-tracking-panel .delivery-live-map-wrap {
            flex: 1;
            min-height: 0;
            padding: 10px;
            display: flex;
            flex-direction: column;
        }

        .result-main--cinema .result-tracking-panel .delivery-live-map {
            flex: 1;
            height: auto !important;
            min-height: 0;
            border-radius: 14px;
        }

        .result-main--cinema .result-tracking-panel .delivery-live-map-hint {
            margin-top: 6px;
            font-size: 0.72rem;
            line-height: 1.25;
        }

        .result-main--cinema .result-actions {
            grid-column: 1;
            grid-row: 2;
            justify-content: flex-start;
            align-self: end;
            margin-top: 0;
            gap: 8px;
        }

        .result-main--cinema .result-actions .btn {
            padding: 0.55rem 0.9rem !important;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 767px) {
        .order-result-page {
            padding: 28px 14px 56px;
        }

        .result-main {
            padding: 32px 20px;
            border-radius: 18px;
        }

        .order-summary {
            grid-template-columns: 1fr;
        }

        .summary-item + .summary-item {
            border-left: 0;
            border-top: 1px solid #e3ece9;
        }

        .result-actions .btn {
            width: 100%;
        }
    }
</style>

<main class="order-result-page">
    <div class="container">
        <div class="result-shell {{ $liveTrackingUrl ? 'has-live-tracking' : '' }}">
            <section class="result-main {{ $liveTrackingUrl ? 'result-main--cinema' : '' }}">
                <div class="result-copy">
                    <div class="result-icon">
                        <i class="bi {{ $isSuccess ? 'bi-check-lg' : ($isFailed ? 'bi-exclamation-lg' : 'bi-x-lg') }}"></i>
                    </div>

                    <p class="text-uppercase text-secondary fw-semibold small mb-2">Chill Drink</p>
                    <h1 class="display-6 fw-bold mb-3">{{ $title }}</h1>
                    <p class="text-secondary fs-5 mb-0 result-message">{{ $message }}</p>

                    @if($order)
                        <div class="order-summary">
                            <div class="summary-item">
                                <span class="summary-label">Mã đơn hàng</span>
                                <strong>{{ $order->displayCode() }}</strong>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Thanh toán</span>
                                <strong>{{ $paymentLabels[$order->payment_method] ?? strtoupper($order->payment_method) }}</strong>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Tổng cộng</span>
                                <strong class="text-primary">{{ number_format((int) $order->total, 0, ',', '.') }}đ</strong>
                            </div>
                            @if($order->scheduled_at)
                            <div class="summary-item">
                                <span class="summary-label">Thời gian muốn nhận</span>
                                <strong class="text-primary"><i class="bi bi-calendar-check me-1"></i>{{ $order->scheduled_at->format('H:i · d/m/Y') }}</strong>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if($order)
                    @if($liveTrackingUrl)
                        <div class="result-tracking-panel text-start">
                            <x-delivery-live-tracking :order="$order" :live-url="$liveTrackingUrl" />
                        </div>
                    @endif

                @endif

                <div class="result-actions">
                    <a href="{{ route('products.index') }}" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-cup-straw me-2"></i>Tiếp tục mua hàng
                    </a>
                    @auth
                        <a href="{{ route('profile.orders') }}" class="btn btn-outline-secondary px-4 py-2">
                            <i class="bi bi-receipt me-2"></i>Xem đơn hàng
                        </a>
                        @if($order && ($order->fulfillment_type ?? 'delivery') === 'delivery' && !in_array(\App\Support\OrderStatus::normalize((string) $order->status), ['cancelled', 'completed'], true))
                            <a href="{{ route('orders.track', $order) }}" class="btn btn-outline-primary px-4 py-2">
                                <i class="bi bi-geo-alt-fill me-2"></i>Theo dõi đơn hàng
                            </a>
                        @endif
                    @endauth
                    @if($order && $deliveryChatMessagesUrl && $deliveryChatSendUrl && ($order->fulfillment_type ?? 'delivery') === 'delivery')
                        <x-order-delivery-chat
                            :order="$order"
                            :messages-url="$deliveryChatMessagesUrl"
                            :send-url="$deliveryChatSendUrl"
                            viewer="customer"
                            peer-label="Tài xế"
                            button-text="Chat với tài xế"
                            button-class="btn btn-outline-primary px-4 py-2" />
                    @endif
                    @if($order && $isFailed && auth()->check() && (int) auth()->id() === (int) $order->user_id)
                        <a href="{{ route('vnpay.payment', $order) }}" class="btn btn-outline-primary px-4 py-2">
                            <i class="bi bi-arrow-repeat me-2"></i>Thanh toán lại
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
