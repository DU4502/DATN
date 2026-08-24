<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Shipper;
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
        | KIỂM TRA TÀI KHOẢN BỊ KHÓA
        |--------------------------------------------------------------------------
        */

        if (! $user->is_active) {

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Tài khoản của bạn đang bị khóa.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SHIPPER
        |--------------------------------------------------------------------------
        */

        if ($user->isShipper()) {

            // Tạo hồ sơ Shipper nếu chưa có
            $shipper = Shipper::firstOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'code' => 'SHIP' . str_pad(
                        $user->id,
                        5,
                        '0',
                        STR_PAD_LEFT
                    ),

                    'phone' => $user->phone ?? '',

                    'vehicle_type' => 'bike',

                    'status' => 'online',
                ]
            );

            // Shipper đã đăng nhập thì mặc định sẵn sàng nhận đơn.
            // Các luồng nghiệp vụ như "đang quay về" vẫn được hiển thị riêng ở dashboard.
            $shipper->forceFill(['status' => 'online'])->save();

            // Cập nhật thông tin đăng nhập
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            // Xóa URL trước đó
            $request->session()->forget('url.intended');

            // Chuyển sang Dashboard Shipper
            return redirect()->route('shipper.dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->isSuperAdmin()) {

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $request->session()->forget('url.intended');

            return redirect()->route('admin.super-admin');
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->isAdmin()) {

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $request->session()->forget('url.intended');

            return redirect()->route('admin.dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | CSKH
        |--------------------------------------------------------------------------
        */

        if ($user->isCskh()) {

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $request->session()->forget('url.intended');

            return redirect()->route('admin.chat.index');
        }

        /*
        |--------------------------------------------------------------------------
        | STAFF / SHIPPER STAFF
        |--------------------------------------------------------------------------
        */

        if ($user->isStaffOnly()) {

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $request->session()->forget('url.intended');

            return redirect()->route('staff.dashboard');
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

        // Nếu đang truy cập chat thì không redirect lại chat
        if (str_contains(
            session('url.intended', ''),
            '/chat'
        )) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(
            route('home', absolute: false)
        );
    }

    /**
     * Đăng xuất
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shipper = $user?->isShipper() ? $user->shipper : null;

        if ($shipper?->hasIncompleteOrders()) {
            return back()->with('error', 'Bạn phải hoàn thành đơn hàng đang giao trước khi đăng xuất.');
        }

        if ($shipper) {
            $shipper->forceFill(['status' => 'offline'])->save();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
