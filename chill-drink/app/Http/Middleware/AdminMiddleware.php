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

        // Check if user is authenticated, active, and is admin
        if (!auth()->check() || $isLocked || !$user->isAdmin()) {
            if (auth()->check() && $isLocked) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.');
            }
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập trang admin.');
        }

        return $next($request);
    }
}
