<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $isLocked = $user && ($user->is_active === false || $user->is_active === 0 || $user->is_active === '0');

        // Check if user is authenticated, active, and is admin/super admin
        if (!auth()->check() || $isLocked || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
            if (auth()->check() && $isLocked) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.');
            }
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập trang admin.');
        }

        // Kiểm tra tài khoản có bị khóa không — force logout nếu bị khóa
        // Dữ liệu tài khoản cũ có thể chưa có giá trị is_active.
        // Chỉ khóa khi trạng thái được ghi rõ là false/0.
        if (auth()->user()->is_active === false) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
