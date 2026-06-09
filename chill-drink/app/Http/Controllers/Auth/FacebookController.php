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

class FacebookController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->hasFacebookConfiguration()) {
            return redirect()->route('login')->with('oauth_error', 'Facebook login chưa được cấu hình đầy đủ. Vui lòng thêm FACEBOOK_CLIENT_ID, FACEBOOK_CLIENT_SECRET và FACEBOOK_REDIRECT_URI.');
        }

        return $this->facebookProvider()->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->hasFacebookConfiguration()) {
            return redirect()->route('login')->with('oauth_error', 'Facebook login chưa được cấu hình đầy đủ. Vui lòng kiểm tra lại file .env.');
        }

        try {
            $facebookUser = $this->facebookProvider()->user();
        } catch (InvalidStateException $exception) {
            report($exception);

            try {
                $facebookUser = Socialite::driver('facebook')->stateless()->user();
            } catch (Throwable $fallbackException) {
                report($fallbackException);

                return redirect()->route('login')->with('oauth_error', 'Phiên đăng nhập Facebook bị mất trạng thái. Hãy thử lại bằng đúng domain localhost hoặc đăng nhập lại.');
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('oauth_error', 'Không thể xác thực với Facebook. Vui lòng thử lại.');
        }

        $user = $this->resolveUserFromFacebook($facebookUser);

        if (! $user) {
            return redirect()->route('register')->with('oauth_error', 'Facebook chưa trả về email hợp lệ. Vui lòng dùng email/password để đăng ký.');
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

    private function resolveUserFromFacebook(object $facebookUser): ?User
    {
        $facebookId = (string) ($facebookUser->getId() ?? '');
        $email = Str::lower((string) ($facebookUser->getEmail() ?? ''));
        $name = trim((string) ($facebookUser->getName() ?: $facebookUser->getNickname() ?: ''));

        if ($facebookId === '' && $email === '') {
            return null;
        }

        $user = User::query()
            ->where(function ($query) use ($facebookId, $email) {
                if ($facebookId !== '') {
                    $query->where('facebook_id', $facebookId);
                }

                if ($email !== '') {
                    $method = $facebookId !== '' ? 'orWhere' : 'where';
                    $query->{$method}('email', $email);
                }
            })
            ->first();

        if ($user) {
            $updates = [];

            if ($facebookId !== '' && blank($user->facebook_id)) {
                $updates['facebook_id'] = $facebookId;
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
            'facebook_id' => $facebookId !== '' ? $facebookId : null,
            'role_id' => 1,
            'is_active' => 1,
            'email_verified_at' => now(),
        ]);
    }

    private function hasFacebookConfiguration(): bool
    {
        return filled(config('services.facebook.client_id'))
            && filled(config('services.facebook.client_secret'))
            && filled(config('services.facebook.redirect'));
    }

    private function facebookProvider(): Provider
    {
        $provider = Socialite::driver('facebook')
            ->scopes(['email']);

        return config('services.facebook.stateless')
            ? $provider->stateless()
            : $provider;
    }
}
