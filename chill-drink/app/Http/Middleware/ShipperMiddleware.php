<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ShipperMiddleware — chỉ cho phép tài khoản shipper (role_id = 5) truy cập khu vực shipper.
 */
class ShipperMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        if (! $user->isShipper()) {
            return redirect()
                ->route('home')
                ->with('error', 'Bạn không có quyền truy cập khu vực shipper.');
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->to('/login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
