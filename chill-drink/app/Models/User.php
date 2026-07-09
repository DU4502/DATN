<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const SUPER_ADMIN_EMAIL = 'superadmin@chilldrink.com';

    /**
     * Các trường được phép fill (Đã đồng bộ với database trong ảnh)
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plain_password',
        'google_id',
        'facebook_id',
        'role_id',
        'phone',
        'address',
        'area',
        'latitude',
        'longitude',
        'branch_id',
        'reset_token',
        'reset_expire',
        'avatar',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reset_expire' => 'datetime',
            'is_active' => 'boolean', // Tự động cast về true/false
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Generate and persist a one-time password reset token.
     */
    public function generatePasswordResetToken(int $ttlMinutes = 60): string
    {
        $plainToken = bin2hex(random_bytes(32));

        $this->forceFill([
            'reset_token' => hash('sha256', $plainToken),
            'reset_expire' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $plainToken;
    }

    /**
     * Determine whether the given reset token is still valid.
     */
    public function hasValidPasswordResetToken(string $plainToken): bool
    {
        if (blank($this->reset_token) || blank($this->reset_expire)) {
            return false;
        }

        return $this->reset_expire->isFuture()
            && hash_equals($this->reset_token, hash('sha256', $plainToken));
    }

    /**
     * Find a user by reset email/token pair.
     */
    public static function findForPasswordReset(string $email, string $plainToken): ?self
    {
        $user = static::where('email', $email)->first();

        return $user && $user->hasValidPasswordResetToken($plainToken) ? $user : null;
    }

    /**
     * Clear the password reset token once it is no longer valid.
     */
    public function clearPasswordResetToken(): void
    {
        $this->forceFill([
            'reset_token' => null,
            'reset_expire' => null,
        ])->save();
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return in_array((int) ($this->role_id ?? 1), [2, 3], true);
    }

    public function isSuperAdmin(): bool
    {
        return (int) ($this->role_id ?? 1) === 3;
    }

    public function isCustomer(): bool
    {
        return ! $this->isAdmin();
    }

    public function scopeCustomers($query)
    {
        return $query->where('role_id', 1);
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role_id', [2, 3]);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user's received vouchers.
     */
    public function vouchers()
    {
        return $this->hasMany(UserVoucher::class);
    }

    /**
     * The current database does not include Laravel's remember_token column.
     */
    public function getRememberToken()
    {
        return $this->attributes[$this->getRememberTokenName()] ?? null;
    }

    public function setRememberToken($value): void
    {
        if (array_key_exists($this->getRememberTokenName(), $this->attributes)) {
            $this->attributes[$this->getRememberTokenName()] = $value;
        }
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
