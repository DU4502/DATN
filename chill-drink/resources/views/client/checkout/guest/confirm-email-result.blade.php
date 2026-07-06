@extends('layouts.client')

@section('title', $status === 'success' ? 'Xác nhận thành công' : 'Xác nhận thất bại')

@section('content')
@php
    $config = match($status) {
        'success' => [
            'icon'       => '✅',
            'bg'         => 'linear-gradient(135deg, #d1fae5, #a7f3d0)',
            'title'      => 'Xác nhận thành công!',
            'color'      => '#0d9373',
            'alert'      => 'success',
            'alert_icon' => 'bi-check2-circle',
        ],
        'already_confirmed' => [
            'icon'       => '✔️',
            'bg'         => 'linear-gradient(135deg, #dbeafe, #bfdbfe)',
            'title'      => 'Đã xác nhận trước đó',
            'color'      => '#1d4ed8',
            'alert'      => 'info',
            'alert_icon' => 'bi-info-circle',
        ],
        'expired' => [
            'icon'       => '⏰',
            'bg'         => 'linear-gradient(135deg, #fef3c7, #fde68a)',
            'title'      => 'Link đã hết hạn',
            'color'      => '#92400e',
            'alert'      => 'warning',
            'alert_icon' => 'bi-exclamation-triangle',
        ],
        default => [
            'icon'       => '❌',
            'bg'         => 'linear-gradient(135deg, #fee2e2, #fecaca)',
            'title'      => 'Xác nhận thất bại',
            'color'      => '#991b1b',
            'alert'      => 'danger',
            'alert_icon' => 'bi-x-circle',
        ],
    };
@endphp

<style>
    .result-page {
        min-height: 70vh;
        display: flex;
        align-items: center;
        background: linear-gradient(180deg, #effcf9 0%, #ffffff 100%);
        padding: 4rem 0;
    }
    .result-card {
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.07);
        border-radius: 28px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.08);
        padding: 3rem 2.5rem;
        text-align: center;
        max-width: 520px;
        margin: 0 auto;
    }
    .icon-circle {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: {{ $config['bg'] }};
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2.4rem;
    }
    .result-title {
        font-size: 1.7rem;
        font-weight: 800;
        color: {{ $config['color'] }};
        margin-bottom: 1rem;
    }
</style>

<section class="result-page">
    <div class="container">
        <div class="result-card">
            <div class="icon-circle">{{ $config['icon'] }}</div>

            <h1 class="result-title">{{ $config['title'] }}</h1>

            <div class="alert alert-{{ $config['alert'] }} text-start py-3 px-4 rounded-3 mb-4" style="font-size:0.95rem;">
                <i class="bi {{ $config['alert_icon'] }} me-2"></i>
                {{ $message }}
            </div>

            @if ($status === 'success')
                <div class="bg-light border rounded-3 p-3 mb-4 text-start" style="font-size:0.9rem; color:#374151;">
                    <div class="fw-semibold mb-2" style="color:#0d9373;"><i class="bi bi-receipt me-1"></i>Thông tin đơn hàng</div>
                    <div>Mã đơn: <strong>#{{ $order->id }}</strong></div>
                    <div>Khách: <strong>{{ $order->guest_name }}</strong></div>
                    <div>Tổng tiền: <strong class="text-primary">{{ number_format((int)($order->total ?? $order->total_price ?? 0), 0, ',', '.') }}đ</strong></div>
                    <div>Thanh toán: <strong>{{ $order->payment_method === 'vnpay' ? 'VNPay' : 'COD' }}</strong></div>
                </div>
                <p class="text-secondary" style="font-size:0.9rem;">
                    Chúng tôi sẽ xử lý đơn hàng và liên hệ với bạn sớm nhất có thể.
                    Cảm ơn bạn đã tin tưởng <strong>Chill Drink</strong>! 🎉
                </p>
            @elseif ($status === 'expired')
                <p class="text-secondary" style="font-size:0.9rem;">
                    Đơn hàng đã hết hạn xác nhận. Bạn có thể đặt lại đơn hàng mới bất kỳ lúc nào.
                </p>
            @elseif ($status === 'already_confirmed')
                <p class="text-secondary" style="font-size:0.9rem;">
                    Đơn hàng của bạn đã được xác nhận và đang được xử lý.
                </p>
            @else
                <p class="text-secondary" style="font-size:0.9rem;">
                    Vui lòng kiểm tra lại đường dẫn hoặc liên hệ với chúng tôi để được hỗ trợ.
                </p>
            @endif

            <hr class="my-4">

            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-house me-1"></i> Về trang chủ
                </a>
                @if ($status === 'expired' || $status === 'invalid')
                    <a href="{{ route('checkout.guest.index') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-arrow-repeat me-1"></i> Đặt lại
                    </a>
                @else
                    <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-bag me-1"></i> Tiếp tục mua sắm
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
