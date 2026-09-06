<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px solid #eaeaea; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; color: #2d3748; margin: 0; }
        .content { color: #4a5568; line-height: 1.6; }
        .order-details { background: #f7fafc; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .order-details h3 { margin-top: 0; color: #2d3748; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
        .item-list li:last-child { border-bottom: none; }
        .total { font-weight: bold; font-size: 18px; margin-top: 15px; text-align: right; color: #e53e3e; }
        .cta-btn { display: block; width: 100%; text-align: center; background-color: #3182ce; color: #ffffff !important; padding: 14px; text-decoration: none; font-weight: bold; border-radius: 6px; margin: 30px 0; font-size: 16px; }
        .points-box { background: #ebf8ff; border: 1px solid #bee3f8; padding: 15px; border-radius: 6px; text-align: center; margin-top: 20px; }
        .points-box p { margin: 0 0 10px; color: #2b6cb0; }
        .register-link { color: #2b6cb0; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cảm ơn bạn đã đặt hàng, {{ $order->guest_name }}!</h1>
        </div>
        
        <div class="content">
            <p>Đơn hàng thức uống của bạn đang được chúng tôi chuẩn bị bằng tất cả sự tận tâm.</p>
            
            <div class="order-details">
                <h3>THÔNG TIN ĐƠN HÀNG {{ $order->displayCode() }}</h3>
                <ul class="item-list">
                    @foreach($order->orderItems ?? [] as $item)
                        <li>
                            <span>{{ $item->product_id }} <!-- Tên SP (Cần join hoặc relation) --> x {{ $item->quantity }}</span>
                            <span>{{ number_format($item->total_price, 0, ',', '.') }}đ</span>
                        </li>
                    @endforeach
                </ul>
                <div class="total">
                    Tổng cộng: {{ number_format($order->total, 0, ',', '.') }}đ
                </div>
            </div>

            <p>Trạng thái đơn hàng:</p>
            <p>Bạn có thể theo dõi tiến độ pha chế và giao hàng bất kỳ lúc nào mà không cần đăng nhập bằng cách bấm vào nút dưới đây:</p>
            
            <a href="{{ $trackingUrl }}" class="cta-btn">XEM TRẠNG THÁI ĐƠN HÀNG</a>
            
            @if($points > 0)
            <div class="points-box">
                <p>🎉 <strong>Quà tặng dành riêng cho bạn!</strong></p>
                <p>Bạn đang có <strong>{{ $points }} điểm thưởng</strong> chờ được kích hoạt từ đơn hàng này. Đăng ký tài khoản ngay để giữ điểm và dùng cho lần đặt đồ uống tới!</p>
                <a href="{{ $trackingUrl }}#register" class="register-link">Đăng ký thành viên</a>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
