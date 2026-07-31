<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CskhMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !(auth()->user()->isCskh() || auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập.');
        }

        // Kiểm tra tài khoản có bị khóa không — force logout nếu bị khóa
        if (!auth()->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
