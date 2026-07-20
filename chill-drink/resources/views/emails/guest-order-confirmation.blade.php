@component('mail::message')
# Xin chào {{ $order->guest_name }},

Cảm ơn bạn đã đặt hàng tại **Chill Drink**!

**Mã đơn:** {{ $order->displayCode() }}  
**Tổng cộng:** {{ number_format((int) $order->total, 0, ',', '.') }}đ  
**Thanh toán:** {{ $order->payment_method === 'vnpay' ? 'VNPay' : 'Thanh toán khi nhận hàng' }}  
**Trạng thái:** {{ \App\Support\OrderStatus::label((string) $order->status) }}

@component('mail::button', ['url' => $trackUrl])
Theo dõi đơn hàng
@endcomponent

Bạn có thể tạo tài khoản với thông tin vừa nhập để tích điểm cho lần mua sau.

Cảm ơn bạn,<br>
{{ config('app.name') }}
@endcomponent
