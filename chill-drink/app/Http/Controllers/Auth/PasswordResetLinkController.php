<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
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
        try {
            $status = $this->usesCustomSmtp()
                ? Password::sendResetLink(
                    $request->only('email'),
                    fn (CanResetPassword $user, string $token) => $this->sendCustomResetEmail($user, $token)
                )
                : Password::sendResetLink($request->only('email'));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Không gửi được email đặt lại mật khẩu. Vui lòng kiểm tra cấu hình SMTP.']);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }

    private function usesCustomSmtp(): bool
    {
        $config = config('services.password_reset');

        return filled($config['smtp_host'] ?? null)
            && filled($config['smtp_username'] ?? null)
            && filled($config['smtp_password'] ?? null)
            && filled($config['from_address'] ?? null);
    }

    private function sendCustomResetEmail(CanResetPassword $user, string $token): string
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

        return Password::RESET_LINK_SENT;
    }
}
