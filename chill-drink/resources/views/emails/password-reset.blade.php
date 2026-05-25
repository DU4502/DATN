<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu Chill Drink</title>
</head>
<body style="margin:0;padding:0;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border:1px solid #dcebe8;border-radius:24px;overflow:hidden;box-shadow:0 14px 30px rgba(23,32,51,0.08);">
            <div style="padding:24px 28px;background:linear-gradient(135deg,#0f8b8d,#3bd6b5);color:#ffffff;">
                <div style="font-size:24px;font-weight:700;">Chill Drink</div>
                <div style="margin-top:8px;font-size:14px;opacity:0.95;">Yêu cầu đặt lại mật khẩu</div>
            </div>

            <div style="padding:28px;">
                <p style="margin:0 0 16px;">Xin chào <strong>{{ $user->name }}</strong>,</p>
                <p style="margin:0 0 16px;line-height:1.7;">
                    Hệ thống vừa nhận được yêu cầu đặt lại mật khẩu cho tài khoản Chill Drink của bạn.
                    Nhấn vào nút bên dưới để tạo mật khẩu mới.
                </p>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $resetLink }}" style="display:inline-block;padding:14px 24px;border-radius:999px;background:#0f8b8d;color:#ffffff;text-decoration:none;font-weight:700;">
                        Đặt lại mật khẩu
                    </a>
                </div>

                <p style="margin:0 0 12px;line-height:1.7;">
                    Link này sẽ hết hạn sau <strong>{{ $expireMinutes }} phút</strong> và chỉ dùng được <strong>1 lần</strong>.
                </p>
                <p style="margin:0 0 16px;line-height:1.7;">
                    Nếu nút không hoạt động, bạn có thể copy link này và dán vào trình duyệt:
                </p>
                <p style="margin:0 0 20px;line-height:1.7;word-break:break-all;">
                    <a href="{{ $resetLink }}" style="color:#0f8b8d;">{{ $resetLink }}</a>
                </p>

                <div style="padding:16px 18px;border-radius:16px;background:#f4f8fb;color:#5f6868;line-height:1.7;">
                    Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Mật khẩu hiện tại của bạn sẽ không thay đổi.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
