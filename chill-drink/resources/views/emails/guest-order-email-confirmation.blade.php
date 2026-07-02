<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng – Chill Drink</title>
    <style>
        /* Reset */
        body, table, td, p, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; }

        body {
            margin: 0;
            padding: 0;
            background-color: #f0faf6;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #2d3748;
        }

        .wrapper {
            max-width: 620px;
            margin: 0 auto;
            padding: 32px 16px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #0d9373 0%, #059e75 60%, #05836a 100%);
            border-radius: 20px 20px 0 0;
            padding: 40px 40px 32px;
            text-align: center;
        }
        .logo-text {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .logo-dot { color: #a7f3d0; }
        .header-tagline {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-top: 6px;
        }

        /* Card body */
        .card {
            background: #ffffff;
            border-radius: 0 0 20px 20px;
            padding: 40px;
            box-shadow: 0 8px 40px rgba(13, 147, 115, 0.10);
        }

        .greeting {
            font-size: 22px;
            font-weight: 700;
            color: #0d9373;
            margin: 0 0 12px;
        }
        .intro-text {
            font-size: 15px;
            line-height: 1.7;
            color: #4a5568;
            margin: 0 0 28px;
        }

        /* Alert box */
        .confirm-alert {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-left: 4px solid #f97316;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 28px;
            font-size: 14px;
            color: #9a3412;
            line-height: 1.6;
        }
        .confirm-alert strong { color: #7c2d12; }

        /* Order info box */
        .order-box {
            background: #f0faf6;
            border: 1px solid #a7f3d0;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }
        .order-box-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #059669;
            margin: 0 0 14px;
        }
        .order-meta {
            font-size: 14px;
            color: #374151;
            margin: 0 0 6px;
        }
        .order-meta span { color: #6b7280; }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0 0;
            font-size: 14px;
        }
        .items-table thead tr {
            border-bottom: 2px solid #d1fae5;
        }
        .items-table thead th {
            padding: 8px 4px;
            text-align: left;
            color: #059669;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table thead th:last-child { text-align: right; }
        .items-table tbody tr {
            border-bottom: 1px solid #ecfdf5;
        }
        .items-table tbody td {
            padding: 10px 4px;
            color: #374151;
            vertical-align: middle;
        }
        .items-table tbody td:last-child { text-align: right; font-weight: 600; }
        .item-name { font-weight: 600; }
        .item-meta { font-size: 12px; color: #9ca3af; margin-top: 2px; }

        /* Total row */
        .total-row {
            border-top: 2px solid #a7f3d0 !important;
            border-bottom: none !important;
        }
        .total-row td {
            padding-top: 14px !important;
            font-size: 16px;
        }
        .total-label { font-weight: 700; color: #0d9373; }
        .total-amount { font-weight: 800; color: #0d9373; font-size: 18px !important; }

        /* CTA Button */
        .cta-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, #0d9373, #059e75);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            padding: 16px 48px;
            border-radius: 100px;
            letter-spacing: 0.3px;
            box-shadow: 0 8px 24px rgba(13, 147, 115, 0.35);
        }
        .cta-btn:hover { background: #059669; }

        .cta-sub {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 10px;
        }

        /* Timer badge */
        .timer-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Fallback link */
        .fallback-link-block {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 16px;
            margin: 20px 0 0;
            font-size: 12px;
            color: #6b7280;
            word-break: break-all;
        }
        .fallback-link-block a { color: #0d9373; }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 28px 0;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 24px 0 8px;
            font-size: 13px;
            color: #9ca3af;
            line-height: 1.8;
        }
        .footer strong { color: #6b7280; }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- Header -->
    <div class="header">
        <div class="logo-text">Chill<span class="logo-dot">.</span>Drink</div>
        <div class="header-tagline">Thức uống ngon – Giao tận nơi</div>
    </div>

    <!-- Card -->
    <div class="card">

        <p class="greeting">Xin chào, {{ $order->guest_name }}! 👋</p>
        <p class="intro-text">
            Cảm ơn bạn đã chọn <strong>Chill Drink</strong>. Chúng tôi đã nhận được đơn hàng
            <strong>#{{ $order->id }}</strong> của bạn.<br><br>
            Để bảo vệ bạn, đơn hàng chưa được xử lý cho đến khi bạn <strong>xác nhận qua email</strong> này.
            Vui lòng nhấn nút bên dưới để xác nhận.
        </p>

        <!-- Warning -->
        <div class="confirm-alert">
            <strong>⏱ Lưu ý:</strong> Link xác nhận có hiệu lực trong vòng
            <strong>15 phút</strong> (hết hạn lúc {{ $expiresAt ? $expiresAt->format('H:i, d/m/Y') : '—' }}).
            Nếu không xác nhận, đơn hàng sẽ tự động bị huỷ.
        </div>

        <!-- Order info -->
        <div class="order-box">
            <div class="order-box-title">Thông tin đơn hàng</div>

            <div class="order-meta">Mã đơn: <span>#{{ $order->id }}</span></div>
            <div class="order-meta">Email: <span>{{ $order->guest_email }}</span></div>
            <div class="order-meta">Số điện thoại: <span>{{ $order->guest_phone }}</span></div>
            <div class="order-meta">
                Thanh toán:
                <span>{{ $order->payment_method === 'vnpay' ? 'VNPay / Quét QR' : 'Thanh toán khi nhận hàng (COD)' }}</span>
            </div>
            @if ($order->delivery_type === 'pickup' && $order->branch)
                <div class="order-meta">Nhận hàng tại: <span>{{ $order->branch->name }}</span></div>
            @endif

            <!-- Items list -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align:center;">SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->orderItems as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item->product?->name ?? 'Sản phẩm #' . $item->product_id }}</div>
                                @if ($item->price)
                                    <div class="item-meta">{{ number_format($item->price, 0, ',', '.') }}đ / cái</div>
                                @endif
                            </td>
                            <td style="text-align:center;">{{ $item->quantity }}</td>
                            <td>{{ number_format($item->total_price ?? ($item->price * $item->quantity), 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach

                    @php
                        $shippingFee = (int) ($order->shipping_fee ?? 0);
                        $total       = (int) ($order->total ?? $order->total_price ?? 0);
                    @endphp

                    @if ($shippingFee > 0)
                        <tr>
                            <td colspan="2" style="color:#6b7280; font-size:13px;">Phí giao hàng</td>
                            <td style="color:#6b7280; font-size:13px;">{{ number_format($shippingFee, 0, ',', '.') }}đ</td>
                        </tr>
                    @endif

                    <tr class="total-row">
                        <td colspan="2" class="total-label">Tổng cộng</td>
                        <td class="total-amount">{{ number_format($total, 0, ',', '.') }}đ</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- CTA -->
        <div class="cta-wrapper">
            <div class="timer-badge">⏳ Hết hạn sau 15 phút</div><br>
            <a href="{{ $confirmUrl }}" class="cta-btn">
                ✅ &nbsp;Xác nhận đơn hàng
            </a>
            <p class="cta-sub">Nhấn nút trên để xác nhận đặt hàng và chúng tôi sẽ bắt đầu chuẩn bị ngay.</p>
        </div>

        <!-- Fallback -->
        <div class="fallback-link-block">
            Nếu nút không hoạt động, hãy sao chép đường dẫn sau vào trình duyệt:<br>
            <a href="{{ $confirmUrl }}">{{ $confirmUrl }}</a>
        </div>

        <hr class="divider">

        <p style="font-size:13px; color:#9ca3af; line-height:1.7; margin:0;">
            Nếu bạn <strong style="color:#6b7280;">không đặt hàng này</strong>, hãy bỏ qua email này.
            Đơn hàng sẽ tự động bị huỷ sau 15 phút và không có bất kỳ khoản phí nào phát sinh.<br><br>
            Cần hỗ trợ? Liên hệ chúng tôi qua email <strong style="color:#0d9373;">{{ config('mail.from.address') }}</strong>.
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>Chill Drink</strong><br>
        © {{ date('Y') }} Chill Drink. All rights reserved.<br>
        Email này được gửi tự động, vui lòng không trả lời.
    </div>

</div>
</body>
</html>
