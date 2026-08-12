<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Hiển thị trang đăng nhập
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Xử lý đăng nhập
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Đăng nhập
        $request->authenticate();

        // Tạo lại session để bảo mật
        $request->session()->regenerate();

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | SHIPPER
        |--------------------------------------------------------------------------
        */

        if ($user->isShipper()) {

            // Kiểm tra tài khoản
            if (!$user->is_active) {

                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors([
                        'email' => 'Tài khoản Shipper của bạn đang bị khóa.',
                    ]);
            }

            // Kiểm tra hồ sơ shipper
            $shipper = \App\Models\Shipper::firstOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'code' => 'SHIP' . str_pad(
                        $user->id,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'phone' => $user->phone ?? '',
                    'vehicle_type' => 'bike',
                    'status' => 'online',
                ]
            );

            // Lưu thông tin đăng nhập
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $request->session()->forget('url.intended');

            return redirect()->route('shipper.dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->isSuperAdmin()) {

            $request->session()->forget('url.intended');

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            return redirect()->route('admin.super-admin');
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->isAdmin()) {

            $request->session()->forget('url.intended');

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            return redirect()->route('admin.dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(
            route('home', absolute: false)
        );
    }

    /**
     * Đăng xuất
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
