<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => (string) $request->query('email', ''),
            'token' => (string) $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::findForPasswordReset($validated['email'], $validated['token']);

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        $passwordData = [
            'password' => Hash::make($validated['password']),
        ];

        if (Schema::hasColumn('users', 'remember_token')) {
            $passwordData['remember_token'] = Str::random(60);
        }

        if (Schema::hasColumn('users', 'reset_token')) {
            $passwordData['reset_token'] = null;
        }

        if (Schema::hasColumn('users', 'reset_expire')) {
            $passwordData['reset_expire'] = null;
        }

        $user->forceFill($passwordData)->save();
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Mật khẩu đã được đặt lại thành công.');
    }
}
