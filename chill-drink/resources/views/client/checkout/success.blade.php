@extends('layouts.client')

@section('title', $result === 'success' ? 'Đặt hàng thành công' : 'Kết quả thanh toán')

@section('content')
@php
    $isSuccess = $result === 'success';
    $isFailed = $result === 'failed';
    $paymentLabels = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'VNPay',
    ];
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
        grid-template-columns: repeat(3, minmax(0, 1fr));
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
        <div class="result-shell">
            <section class="result-main">
                <div class="result-icon">
                    <i class="bi {{ $isSuccess ? 'bi-check-lg' : ($isFailed ? 'bi-exclamation-lg' : 'bi-x-lg') }}"></i>
                </div>

                <p class="text-uppercase text-secondary fw-semibold small mb-2">Chill Drink</p>
                <h1 class="display-6 fw-bold mb-3">{{ $title }}</h1>
                <p class="text-secondary fs-5 mb-0">{{ $message }}</p>

                @if($order)
                    <div class="order-summary">
                        <div class="summary-item">
                            <span class="summary-label">Mã đơn hàng</span>
                            <strong>#{{ $order->id }}</strong>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Thanh toán</span>
                            <strong>{{ $paymentLabels[$order->payment_method] ?? strtoupper($order->payment_method) }}</strong>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Tổng cộng</span>
                            <strong class="text-primary">{{ number_format((int) $order->total, 0, ',', '.') }}đ</strong>
                        </div>
                    </div>
                @endif

                <div class="result-actions">
                    <a href="{{ route('products.index') }}" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-cup-straw me-2"></i>Tiếp tục mua hàng
                    </a>
                    @auth
                        <a href="{{ route('profile.orders') }}" class="btn btn-outline-secondary px-4 py-2">
                            <i class="bi bi-receipt me-2"></i>Xem đơn hàng
                        </a>
                    @endauth
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
