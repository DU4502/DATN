<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EmailVerificationCodeService
{
    private const TTL_MINUTES = 10;

    public function send(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $this->cache()->put($this->cacheKey($user), Hash::make($code), now()->addMinutes(self::TTL_MINUTES));

        $user->notify(new EmailVerificationCodeNotification($code, self::TTL_MINUTES));
    }

    public function sendToEmail(string $email): void
    {
        $code = (string) random_int(100000, 999999);
        $normalizedEmail = $this->normalizeEmail($email);

        $this->cache()->put($this->emailCacheKey($normalizedEmail), Hash::make($code), now()->addMinutes(self::TTL_MINUTES));

        Notification::route('mail', $normalizedEmail)
            ->notify(new EmailVerificationCodeNotification($code, self::TTL_MINUTES));
    }

    public function verify(User $user, string $code): bool
    {
        $hash = $this->cache()->get($this->cacheKey($user));

        if (! is_string($hash) || ! Hash::check($code, $hash)) {
            return false;
        }

        $this->cache()->forget($this->cacheKey($user));

        return true;
    }

    public function verifyEmail(string $email, string $code): bool
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $hash = $this->cache()->get($this->emailCacheKey($normalizedEmail));

        if (! is_string($hash) || ! Hash::check($code, $hash)) {
            return false;
        }

        $this->cache()->forget($this->emailCacheKey($normalizedEmail));
        $this->cache()->put($this->verifiedEmailCacheKey($normalizedEmail), true, now()->addMinutes(self::TTL_MINUTES));

        return true;
    }

    public function hasVerifiedEmail(string $email): bool
    {
        return $this->cache()->has($this->verifiedEmailCacheKey($this->normalizeEmail($email)));
    }

    public function forgetVerifiedEmail(string $email): void
    {
        $this->cache()->forget($this->verifiedEmailCacheKey($this->normalizeEmail($email)));
    }

    private function cacheKey(User $user): string
    {
        return 'email-verification-code:user:'.$user->getKey();
    }

    private function emailCacheKey(string $email): string
    {
        return 'email-verification-code:email:'.sha1($email);
    }

    private function verifiedEmailCacheKey(string $email): string
    {
        return 'email-verification-verified:email:'.sha1($email);
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function cache()
    {
        return Cache::store('file');
    }
}
