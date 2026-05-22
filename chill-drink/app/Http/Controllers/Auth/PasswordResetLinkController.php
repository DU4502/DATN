<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $key = 'password-reset:' . $request->input('email');

        // Kiểm tra nếu người dùng đã gửi trong vòng 60 giây qua
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Vui lòng đợi {$seconds} giây trước khi yêu cầu gửi lại email mới."]);
        }

        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Email này không tồn tại trong hệ thống.',
        ]);

        $user = User::where('email', $request->input('email'))->firstOrFail();

        // Đánh dấu một lần gửi, hiệu lực trong 60 giây
        RateLimiter::hit($key, 60);

        $expireMinutes = (int) config('services.password_reset.expire_minutes', 60);
        $plainToken = $user->generatePasswordResetToken($expireMinutes);
        $resetLink = route('password.reset', ['token' => $plainToken, 'email' => $user->email]);

        try {
            $this->sendResetEmail($user, $resetLink, $expireMinutes);
        } catch (\Throwable $exception) {
            report($exception);
            $user->clearPasswordResetToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Lỗi: ' . $exception->getMessage()]);
        }

        return back()->with('status', 'Liên kết đặt lại mật khẩu đã được gửi tới email của bạn.');
    }

    /**
     * Send the password reset email via Gmail SMTP.
     *
     * @throws MailException
     */
    protected function sendResetEmail(User $user, string $resetLink, int $expireMinutes): void
    {
        $smtpHost = config('services.password_reset.smtp_host');
        $smtpPort = (int) config('services.password_reset.smtp_port', 587);
        $smtpEncryption = config('services.password_reset.smtp_encryption', 'tls');
        $smtpUsername = config('services.password_reset.smtp_username');
        $smtpPassword = config('services.password_reset.smtp_password');
        $fromAddress = config('services.password_reset.from_address');
        $fromName = config('services.password_reset.from_name', 'Chill Drink');

        if (blank($smtpHost) || blank($smtpUsername) || blank($smtpPassword) || blank($fromAddress)) {
            throw new RuntimeException('Password reset SMTP configuration is incomplete.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = $smtpEncryption;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($user->email, $user->name);
        $mail->isHTML(true);
        $mail->Subject = 'Chill Drink - Đặt lại mật khẩu';
        $mail->Body = view('emails.password-reset', [
            'user' => $user,
            'resetLink' => $resetLink,
            'expireMinutes' => $expireMinutes,
        ])->render();
        $mail->AltBody = "Xin chào {$user->name},\n\n"
            . "Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản Chill Drink.\n"
            . "Nhấn vào liên kết sau để đặt lại mật khẩu: {$resetLink}\n\n"
            . "Liên kết này có hiệu lực trong {$expireMinutes} phút và chỉ dùng được một lần.\n"
            . "Nếu bạn không yêu cầu thao tác này, hãy bỏ qua email này.";

        $mail->send();
    }
}
