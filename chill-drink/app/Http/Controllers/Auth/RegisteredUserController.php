<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use App\Services\FirebasePhoneAuthService;
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
    public function store(
        RegisterRequest $request,
        EmailVerificationCodeService $verificationCodes,
        FirebasePhoneAuthService $phoneAuth
    ): RedirectResponse
    {
        $validated = $request->validated();

        $contact = trim((string) ($validated['contact'] ?? ''));
        $contactType = (string) ($validated['contact_type'] ?? '');

        if ($contactType === 'email') {
            if (! $verificationCodes->hasVerifiedEmail($contact)) {
                return back()
                    ->withErrors(['email_verification_code' => 'Vui lòng bấm xác minh Gmail trước khi đăng ký.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            if (User::query()->where('email', $contact)->exists()) {
                return back()
                    ->withErrors(['contact' => 'Email này đã được đăng ký.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            $userData = [
                'name' => $validated['name'],
                'email' => $contact,
                'password' => Hash::make($validated['password']),
                'role_id' => 1,
                'is_active' => 1,
                'email_verified_at' => now(),
            ];

            if (Schema::hasColumn('users', 'phone')) {
                $userData['phone'] = $validated['phone'] ?? null;
            }

            foreach (['address', 'area'] as $field) {
                if (Schema::hasColumn('users', $field)) {
                    $userData[$field] = $validated[$field] ?? null;
                }
            }

            $user = User::create($userData);

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            $verificationCodes->forgetVerifiedEmail($contact);
        } else {
            try {
                $phoneData = $phoneAuth->verifyPhoneTokenMatches(
                    (string) ($validated['firebase_id_token'] ?? ''),
                    $contact
                );
            } catch (\Throwable $exception) {
                report($exception);

                return back()
                    ->withErrors(['contact' => 'Số điện thoại chưa được xác minh. Vui lòng lấy mã và xác minh lại.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            $phoneLocal = $phoneData['local'];
            $phoneIntl = $phoneData['international'];

            if (
                User::query()
                    ->where('phone', $phoneLocal)
                    ->orWhere('phone', $phoneIntl)
                    ->exists()
            ) {
                return back()
                    ->withErrors(['contact' => 'Số điện thoại này đã được đăng ký.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            $userData = [
                'name' => $validated['name'],
                'email' => $phoneAuth->makePhoneEmail($phoneLocal),
                'phone' => $phoneLocal,
                'password' => Hash::make($validated['password']),
                'role_id' => 1,
                'is_active' => 1,
                'email_verified_at' => now(),
            ];

            foreach (['address', 'area'] as $field) {
                if (Schema::hasColumn('users', $field)) {
                    $userData[$field] = $validated[$field] ?? null;
                }
            }

            $user = User::create($userData);

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        Auth::login($user);
        $request->session()->forget('url.intended');

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
