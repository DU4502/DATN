<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Gọi hàm authenticate() từ LoginRequest để đăng nhập
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()->isSuperAdmin()) {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.super-admin');
        }

        if ($request->user()->isAdmin()) {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->isCskh()) {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.chat.index');
        }

        if ($request->user()->isStaffOnly()) {
            $request->session()->forget('url.intended');

            return redirect()->route('staff.dashboard');
        }

        if (str_contains(session('url.intended', ''), '/chat')) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
