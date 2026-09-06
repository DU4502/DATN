<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isLocked = $user && ($user->is_active === false || $user->is_active === 0 || $user->is_active === '0');

        if (!$user || $isLocked || !$user->isSuperAdmin()) {
            if ($user && $isLocked) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.');
            }
            return redirect()->route('admin.dashboard')
                ->with('error', 'Bạn không có quyền truy cập khu vực Super Admin.');
        }

        return $next($request);
    }
}
