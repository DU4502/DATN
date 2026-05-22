<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email', '');
        $token = (string) $request->route('token');
        $user = User::findForPasswordReset($email, $token);

        if (! $user) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        return view('auth.reset-password', [
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ], [
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        $user = User::findForPasswordReset($request->input('email'), $request->input('token'));

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ, đã hết hạn hoặc đã được sử dụng.']);
        }

        $hashedPassword = password_hash($request->input('password'), PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => 'Không thể tạo mật khẩu mới. Vui lòng thử lại.']);
        }

        $user->forceFill([
            'password' => $hashedPassword,
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
            'reset_token' => null,
            'reset_expire' => null,
        ])->save();

        event(new PasswordReset($user));

        return redirect()
            ->route('login')
            ->with('status', 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập ngay.');
    }
}
