<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, EmailVerificationCodeService $verificationCodes): RedirectResponse
    {
        $validated = $request->validated();

        if (! $verificationCodes->hasVerifiedEmail($validated['email'])) {
            return back()
                ->withErrors(['email_verification_code' => 'Vui lòng bấm xác minh Gmail trước khi đăng ký.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => 1,
            'is_active' => 1,
        ];

        foreach (['phone', 'address', 'area'] as $field) {
            if (Schema::hasColumn('users', $field)) {
                $userData[$field] = $validated[$field] ?? null;
            }
        }

        $user = User::create($userData);

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $verificationCodes->forgetVerifiedEmail($validated['email']);

        Auth::login($user);
        $request->session()->forget('url.intended');

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
