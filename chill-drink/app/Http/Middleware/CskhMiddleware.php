<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CskhMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $isLocked = $user && ($user->is_active === false || $user->is_active === 0 || $user->is_active === '0');

        if (!auth()->check() || $isLocked || (!$user->isCskh() && !$user->isAdmin() && !$user->isSuperAdmin())) {
            if (auth()->check() && $isLocked) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.');
            }
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập CSKH.');
        }

        // Kiểm tra tài khoản có bị khóa không — force logout nếu bị khóa
        if (!auth()->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
