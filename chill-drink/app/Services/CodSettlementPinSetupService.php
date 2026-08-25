<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CodSettlementPinSetupCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class CodSettlementPinSetupService
{
    private const TTL_MINUTES = 10;

    public function send(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $this->cache()->put(
            $this->cacheKey($user),
            Hash::make($code),
            now()->addMinutes(self::TTL_MINUTES)
        );

        $user->notify(new CodSettlementPinSetupCodeNotification($code, self::TTL_MINUTES));
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

    public function ttlMinutes(): int
    {
        return self::TTL_MINUTES;
    }

    private function cacheKey(User $user): string
    {
        return 'cod-settlement-pin-setup-code:user:'.$user->getKey();
    }

    private function cache()
    {
        return Cache::store('file');
    }
}
