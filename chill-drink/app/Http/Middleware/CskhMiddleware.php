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

        return $next($request);
    }
}
