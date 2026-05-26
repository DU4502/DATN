<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

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
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->first();

        // Always return the same message to avoid leaking account existence.
        if (! $user) {
            return back()->with('status', 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.');
        }

        $expireMinutes = (int) (config('auth.passwords.users.expire') ?? 60);
        $token = $user->generatePasswordResetToken($expireMinutes);

        try {
            if ($this->usesCustomSmtp()) {
                $this->sendCustomResetEmail($user, $token);
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Không gửi được email đặt lại mật khẩu. Vui lòng kiểm tra cấu hình SMTP.']);
        }

        return back()->with('status', 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.');
    }

    private function usesCustomSmtp(): bool
    {
        $config = config('services.password_reset');

        return filled($config['smtp_host'] ?? null)
            && filled($config['smtp_username'] ?? null)
            && filled($config['smtp_password'] ?? null)
            && filled($config['from_address'] ?? null);
    }

    private function sendCustomResetEmail(User $user, string $token): void
    {
        $config = config('services.password_reset');
        $email = $user->getEmailForPasswordReset();
        $resetLink = route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);
        $expireMinutes = (int) ($config['expire_minutes'] ?? 60);

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = (string) $config['smtp_host'];
        $mail->Port = (int) ($config['smtp_port'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = (string) $config['smtp_username'];
        $mail->Password = (string) $config['smtp_password'];

        $encryption = strtolower((string) ($config['smtp_encryption'] ?? 'tls'));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom((string) $config['from_address'], (string) ($config['from_name'] ?? config('app.name')));
        $mail->addAddress($email, $user->name ?? '');
        $mail->isHTML(true);
        $mail->Subject = 'Đặt lại mật khẩu Chill Drink';
        $mail->Body = view('emails.password-reset', [
            'user' => $user,
            'resetLink' => $resetLink,
            'expireMinutes' => $expireMinutes,
        ])->render();
        $mail->AltBody = "Đặt lại mật khẩu Chill Drink: {$resetLink}";

        if (! $mail->send()) {
            throw new RuntimeException('PHPMailer could not send the password reset email.');
        }
    }
}
