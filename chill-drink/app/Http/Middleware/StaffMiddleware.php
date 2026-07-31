<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * StaffMiddleware — cho phép nhân viên (role_id = 5) truy cập staff panel.
 * Admin và SuperAdmin cũng có thể xem staff panel qua route riêng.
 */
class StaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Chỉ nhân viên (role_id = 5) mới dùng staff panel
        // Admin (2,3) và CSKH (4) có panel riêng
        if (!$user->isStaffOnly()) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập khu vực này.');
        }

        // Kiểm tra tài khoản có bị khóa không — force logout nếu bị khóa
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
