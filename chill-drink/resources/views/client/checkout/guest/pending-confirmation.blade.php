@extends('layouts.client')

@section('title', 'Kiểm tra email của bạn')

@section('content')
<style>
    .pending-page {
        min-height: 70vh;
        display: flex;
        align-items: center;
        background: linear-gradient(180deg, #effcf9 0%, #ffffff 100%);
        padding: 4rem 0;
    }
    .pending-card {
        background: #ffffff;
        border: 1px solid rgba(0,139,122,0.12);
        border-radius: 28px;
        box-shadow: 0 24px 60px rgba(8,42,38,0.08);
        padding: 3rem 2.5rem;
        text-align: center;
        max-width: 560px;
        margin: 0 auto;
    }
    .icon-circle {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2.4rem;
    }
    .pending-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0d9373;
        margin-bottom: 0.75rem;
    }
    .pending-email {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
    }
    .info-box {
        background: #f0faf6;
        border: 1px solid #a7f3d0;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        margin: 1.5rem 0;
        text-align: left;
    }
    .timer-badge {
        display: inline-block;
        background: #fff7ed;
        color: #92400e;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 4px 14px;
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
</style>

<section class="pending-page">
    <div class="container">
        <div class="pending-card">
            <div class="icon-circle">📧</div>

            <div class="timer-badge">⏳ Hết hạn sau 15 phút</div>

            <h1 class="pending-title">Kiểm tra hộp thư của bạn!</h1>

            <p class="text-secondary mb-1">Chúng tôi đã gửi email xác nhận đến:</p>
            <p class="pending-email mb-0">{{ $order->guest_email }}</p>

            <div class="info-box mt-4">
                <div class="fw-semibold mb-2" style="color:#0d9373;"><i class="bi bi-info-circle me-1"></i>Bước tiếp theo</div>
                <ol class="mb-0 ps-3" style="font-size:0.95rem; color:#374151; line-height:2;">
                    <li>Mở email từ <strong>Chill Drink</strong></li>
                    <li>Nhấn nút <strong>"Xác nhận đơn hàng"</strong></li>
                    <li>Đơn hàng sẽ được xử lý ngay sau đó</li>
                </ol>
            </div>

            <div class="alert alert-warning text-start py-2 px-3 rounded-3" style="font-size:0.88rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Nếu không nhấn xác nhận trong <strong>15 phút</strong>, đơn hàng sẽ <strong>không được xử lý</strong>.
                Không có khoản phí nào phát sinh.
            </div>

            <div class="text-secondary mt-3" style="font-size:0.85rem;">
                <i class="bi bi-envelope me-1"></i>
                Không thấy email? Kiểm tra thư mục <strong>Spam</strong> hoặc <strong>Quảng cáo</strong>.
            </div>

            <hr class="my-4">

            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-house me-1"></i> Về trang chủ
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-bag me-1"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
