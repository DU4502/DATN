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
    $guestConvert = $guestConvert ?? session('guest_convert');
    $showGuestConvert = $isSuccess && $order && $order->isGuest() && !auth()->check() && !empty($guestConvert);
    $statusSteps = [
        \App\Support\OrderStatus::PENDING => 'Đã tiếp nhận',
        \App\Support\OrderStatus::IN_PROGRESS => 'Đang chuẩn bị',
        \App\Support\OrderStatus::SHIPPER_ACCEPTED => 'Đang giao',
        \App\Support\OrderStatus::ARRIVED => 'Sẵn sàng',
        \App\Support\OrderStatus::COMPLETED => 'Hoàn thành',
    ];
    $currentStatus = \App\Support\OrderStatus::normalize((string) ($order->status ?? 'pending'));
    $currentIndex = array_search($currentStatus, array_keys($statusSteps), true);
    if ($currentIndex === false) {
        $currentIndex = 0;
    }
    $pointsEarnable = $order ? $order->pointsEarnable() : 0;
@endphp

<style>
    .order-result-page {
        min-height: calc(100vh - 88px);
        padding: 64px 0 88px;
        background: linear-gradient(180deg, #effcf8 0%, #f8fbfa 52%, #ffffff 100%);
    }

    .result-shell { max-width: 880px; margin: 0 auto; }

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

    .email-notice {
        margin-top: 20px;
        padding: 14px 18px;
        border-radius: 14px;
        background: #f0faf7;
        border: 1px solid #cceee4;
        color: #24574d;
        font-size: 0.98rem;
    }

    .status-timeline {
        margin-top: 28px;
        padding: 24px;
        border: 1px solid #e3ece9;
        border-radius: 16px;
        text-align: left;
    }

    .status-track {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        position: relative;
        margin-bottom: 0.75rem;
    }

    .status-track::before {
        content: '';
        position: absolute;
        top: 11px;
        left: 8%;
        right: 8%;
        height: 3px;
        background: #e5ecea;
        z-index: 0;
    }

    .status-node {
        position: relative;
        z-index: 1;
        text-align: center;
        flex: 1;
    }

    .status-dot {
        width: 24px;
        height: 24px;
        margin: 0 auto 8px;
        border-radius: 50%;
        background: #e5ecea;
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #e5ecea;
    }

    .status-node.is-active .status-dot,
    .status-node.is-done .status-dot {
        background: #0d9373;
        box-shadow: 0 0 0 1px #0d9373;
    }

    .status-node.is-active .status-dot {
        animation: pulse-dot 1.4s infinite;
    }

    .status-label {
        font-size: 0.72rem;
        color: #71807c;
        font-weight: 600;
    }

    .status-node.is-active .status-label,
    .status-node.is-done .status-label {
        color: #0d9373;
    }

    @keyframes pulse-dot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(13, 147, 115, 0.35); }
        50% { box-shadow: 0 0 0 8px rgba(13, 147, 115, 0); }
    }

    .convert-card {
        margin-top: 28px;
        padding: 24px;
        border-radius: 18px;
        border: 2px solid #0d9373;
        background: linear-gradient(135deg, #f0faf7 0%, #ffffff 100%);
        text-align: left;
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

    .summary-item { padding: 20px; background: #fbfefd; }
    .summary-item + .summary-item { border-left: 1px solid #e3ece9; }

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
        .order-result-page { padding: 28px 14px 56px; }
        .result-main { padding: 32px 20px; border-radius: 18px; }
        .order-summary { grid-template-columns: 1fr; }
        .summary-item + .summary-item { border-left: 0; border-top: 1px solid #e3ece9; }
        .status-label { font-size: 0.65rem; }
        .result-actions .btn { width: 100%; }
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

                @if($isSuccess && $order && $order->isGuest() && $order->guest_email)
                    <div class="email-notice">
                        <i class="bi bi-envelope-check me-2"></i>
                        Một email xác nhận kèm chi tiết đơn hàng đã được gửi đến <strong>{{ $order->guest_email }}</strong>
                    </div>
                @endif

                @if($order && $isSuccess)
                    <div class="status-timeline">
                        <div class="fw-semibold mb-3">Trạng thái đơn hàng</div>
                        <div class="status-track">
                            @foreach($statusSteps as $slug => $label)
                                @php
                                    $index = $loop->index;
                                    $stateClass = $index < $currentIndex ? 'is-done' : ($index === $currentIndex ? 'is-active' : '');
                                @endphp
                                <div class="status-node {{ $stateClass }}">
                                    <div class="status-dot"></div>
                                    <div class="status-label">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="small text-secondary">
                            Đơn #{{ $order->id }} · {{ $paymentLabels[$order->payment_method] ?? strtoupper($order->payment_method) }} · {{ number_format((int) $order->total, 0, ',', '.') }}đ
                        </div>
                    </div>
                @endif

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

                @if($showGuestConvert)
                    <div class="convert-card">
                        <div class="d-flex align-items-start gap-3">
                            <span class="checkout-step"><i class="bi bi-stars"></i></span>
                            <div class="flex-grow-1">
                                <h2 class="h5 fw-bold mb-2">Tạo tài khoản với thông tin này để tích điểm cho lần sau</h2>
                                <p class="text-secondary mb-2">
                                    {{ $guestConvert['name'] ?? '' }} · {{ $guestConvert['phone'] ?? '' }} · {{ $guestConvert['email'] ?? '' }}
                                </p>
                                @if($pointsEarnable > 0)
                                    <p class="small text-primary fw-semibold mb-3">Nhận ngay {{ number_format($pointsEarnable, 0, ',', '.') }} điểm từ đơn vừa đặt!</p>
                                @endif
                                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#guestConvertModal">
                                    Tạo tài khoản ngay — chỉ cần thêm mật khẩu
                                </button>
                            </div>
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
                    @elseif($order && $order->isGuest())
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary px-4 py-2">
                            <i class="bi bi-envelope me-2"></i>Đăng nhập để theo dõi
                        </a>
                    @endauth
                    @if($order && $isFailed && \App\Support\GuestOrderAccess::canView($order))
                        <a href="{{ route('vnpay.payment', $order) }}" class="btn btn-outline-primary px-4 py-2">
                            <i class="bi bi-arrow-repeat me-2"></i>Thanh toán lại
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</main>

@if($showGuestConvert)
    <div class="modal fade" id="guestConvertModal" tabindex="-1" aria-labelledby="guestConvertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="guestConvertModalLabel">Tạo tài khoản Chill Drink</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form method="POST" action="{{ route('register.guest-convert') }}" id="guestConvertForm">
                    @csrf
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" value="{{ $guestConvert['name'] ?? '' }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" value="{{ $guestConvert['phone'] ?? '' }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ $guestConvert['email'] ?? '' }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="guest_password" class="form-label">Mật khẩu *</label>
                            <input type="password" id="guest_password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Tối thiểu 8 ký tự</div>
                        </div>
                        <div class="mb-2">
                            <label for="guest_password_confirmation" class="form-label">Xác nhận mật khẩu *</label>
                            <input type="password" id="guest_password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
                        </div>
                    </div>
                    <div class="modal-footer border-0 flex-column align-items-stretch">
                        <button type="submit" class="btn btn-primary rounded-pill py-2">
                            Hoàn tất @if($pointsEarnable > 0)& tích {{ number_format($pointsEarnable, 0, ',', '.') }} điểm 🎁@endif
                        </button>
                        <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">Bỏ qua, tôi sẽ đăng ký sau</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->has('password'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('guestConvertModal')).show();
            });
        </script>
    @endif
@endif
@endsection
