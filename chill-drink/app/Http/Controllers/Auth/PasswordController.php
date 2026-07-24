<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:7',
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (! preg_match('/[A-Z]/u', $value)) {
                        $fail('Mật khẩu mới cần có ít nhất 1 chữ in hoa.');
                    }

                    if (! preg_match('/\d/', $value)) {
                        $fail('Mật khẩu mới cần có ít nhất 1 số.');
                    }

                    if (! preg_match('/[^A-Za-z0-9]/u', $value)) {
                        $fail('Mật khẩu mới cần có ít nhất 1 ký tự đặc biệt, ví dụ @ hoặc !.');
                    }
                },
            ],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không chính xác.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải trên 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        if (Hash::check($validated['password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
            ])->errorBag('updatePassword');
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
