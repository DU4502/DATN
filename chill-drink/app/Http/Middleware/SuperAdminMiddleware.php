<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isSuperAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Bạn không có quyền truy cập giao diện super admin.');
        }

        return $next($request);
    }
}
