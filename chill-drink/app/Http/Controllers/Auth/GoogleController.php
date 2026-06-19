<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('login')->with('oauth_error', 'Google login chưa được cấu hình đầy đủ. Vui lòng thêm GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET và GOOGLE_REDIRECT_URI.');
        }

        return $this->googleProvider()
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('login')->with('oauth_error', 'Google login chưa được cấu hình đầy đủ. Vui lòng kiểm tra lại file .env.');
        }

        try {
            $googleUser = $this->googleProvider()->user();
        } catch (InvalidStateException $exception) {
            report($exception);

            try {
                $googleUser = Socialite::driver('google')->stateless()->user();
            } catch (Throwable $fallbackException) {
                report($fallbackException);

                return redirect()->route('login')->with('oauth_error', 'Phiên đăng nhập Google bị mất trạng thái. Hãy thử lại bằng cùng một domain hoặc đăng nhập lại.');
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('oauth_error', 'Không thể xác thực với Google. Vui lòng thử lại.');
        }

        $user = $this->resolveUserFromGoogle($googleUser);

        if (! $user) {
            return redirect()->route('register')->with('oauth_error', 'Google chưa trả về email hợp lệ. Vui lòng dùng email/password để đăng ký.');
        }

        if (Schema::hasColumn('users', 'is_active') && ! (bool) $user->is_active) {
            return redirect()->route('login')->with('oauth_error', 'Tài khoản của bạn đã bị khóa.');
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        request()->session()->forget('url.intended');

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }

    private function resolveUserFromGoogle(object $googleUser): ?User
    {
        $googleId = (string) ($googleUser->getId() ?? '');
        $email = Str::lower((string) ($googleUser->getEmail() ?? ''));
        $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: ''));

        if ($googleId === '' && $email === '') {
            return null;
        }

        $user = User::query()
            ->where(function ($query) use ($googleId, $email) {
                if ($googleId !== '') {
                    $query->where('google_id', $googleId);
                }

                if ($email !== '') {
                    $method = $googleId !== '' ? 'orWhere' : 'where';
                    $query->{$method}('email', $email);
                }
            })
            ->first();

        if ($user) {
            $updates = [];

            if ($googleId !== '' && blank($user->google_id)) {
                $updates['google_id'] = $googleId;
            }

            if ($email !== '' && blank($user->email)) {
                $updates['email'] = $email;
            }

            if ($name !== '' && blank($user->name)) {
                $updates['name'] = $name;
            }

            if (Schema::hasColumn('users', 'email_verified_at') && blank($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }

            return $user;
        }

        if ($email === '') {
            return null;
        }

        return User::create([
            'name' => $name !== '' ? $name : Str::before($email, '@'),
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'google_id' => $googleId !== '' ? $googleId : null,
            'role_id' => 1,
            'is_active' => 1,
            'email_verified_at' => now(),
        ]);
    }

    private function hasGoogleConfiguration(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function googleProvider(): Provider
    {
        $provider = Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email']);

        return config('services.google.stateless')
            ? $provider->stateless()
            : $provider;
    }
}
