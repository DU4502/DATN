<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationCodeController extends Controller
{
    public function sendRegistrationCode(Request $request, EmailVerificationCodeService $verificationCodes): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Vui lòng nhập email trước khi gửi mã.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $verificationCodes->sendToEmail($validated['email']);

        return response()->json([
            'message' => 'Mã xác minh đã được gửi tới email của bạn.',
        ]);
    }

    public function verifyRegistrationCode(Request $request, EmailVerificationCodeService $verificationCodes): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'email_verification_code' => ['required', 'digits:6'],
        ], [
            'email.required' => 'Vui lòng nhập Gmail trước khi xác minh.',
            'email.email' => 'Email không đúng định dạng.',
            'email_verification_code.required' => 'Vui lòng nhập mã xác minh.',
            'email_verification_code.digits' => 'Mã xác minh phải gồm 6 chữ số.',
        ]);

        if (! $verificationCodes->verifyEmail($validated['email'], $validated['email_verification_code'])) {
            return response()->json([
                'message' => 'Mã xác minh không đúng hoặc đã hết hạn.',
                'errors' => [
                    'email_verification_code' => ['Mã xác minh không đúng hoặc đã hết hạn.'],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Gmail đã được xác minh.',
        ]);
    }

    public function send(Request $request, EmailVerificationCodeService $verificationCodes): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $verificationCodes->send($request->user());

        return back()->with('status', 'verification-code-sent');
    }

    public function verify(Request $request, EmailVerificationCodeService $verificationCodes): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Vui lòng nhập mã xác minh.',
            'code.digits' => 'Mã xác minh phải gồm 6 chữ số.',
        ]);

        if (! $verificationCodes->verify($request->user(), $validated['code'])) {
            return back()
                ->withErrors(['code' => 'Mã xác minh không đúng hoặc đã hết hạn.'])
                ->withInput();
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
