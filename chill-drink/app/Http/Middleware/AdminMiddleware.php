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
        // Check if user is authenticated and is admin
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập trang admin.');
        }

        return $next($request);
    }
}
